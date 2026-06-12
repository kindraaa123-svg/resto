<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Branch;
use App\Models\Menu;
use App\Models\Package;
use App\Models\Promo;
use App\Models\PromoFreebie;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        $query = QueryBuilder::for(Promo::class)
            ->with(['package', 'menu', 'branch', 'addons', 'promoFreebie'])
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->whereHas('package', function ($sq) use ($value) {
                            $sq->where('packagename', 'LIKE', "%{$value}%");
                        })->orWhereHas('menu', function ($sq) use ($value) {
                            $sq->where('name', 'LIKE', "%{$value}%");
                        });
                    });
                }),
                'branchid'
            ])
            ->defaultSort('-packageid');

        $allPromos = $query->get();

        $grouped = $allPromos->groupBy(function ($item) {
            // Group by package name ID + branch + price + status + dates + days
            return $item->packagename_id.'|'.
                   $item->branchid.'|'.
                   $item->price.'|'.
                   $item->status.'|'.
                   ($item->datefrom ?? '').'|'.
                   ($item->dateto ?? '').'|'.
                   json_encode($item->days ?? []);
        })->map(function ($group) {
            $first = $group->first();

            return [
                'packageid' => $first->packagename_id, // For UI grouping reference
                'packagename' => $first->package->packagename ?? 'Unknown',
                'branchid' => $first->branchid,
                'branchname' => $first->branch->branchname ?? 'All Branches',
                'price' => $first->price,
                'status' => $first->status,
                'datefrom' => $first->datefrom,
                'dateto' => $first->dateto,
                'timefrom' => $first->timefrom,
                'timeto' => $first->timeto,
                'days' => $first->days,
                'menus' => $group->whereNotNull('menuid')->values()->map(fn ($p) => [
                    'promoid' => $p->packageid,
                    'menuid' => $p->menuid,
                    'menuname' => $p->menu->name ?? 'Unknown',
                    'qty' => $p->qty,
                    'addons' => $p->addons->map(fn ($a) => [
                        'addonid' => $a->addonid,
                        'name' => $a->name,
                        'price' => $a->price,
                    ]),
                ]),
                'freebies' => $group->whereNotNull('freeid')->values()->map(fn ($p) => [
                    'promoid' => $p->packageid,
                    'freeid' => $p->freeid,
                    'name' => $p->promoFreebie->name ?? 'Unknown',
                    'qty' => $p->qty,
                ]),
                'all_promoids' => $group->pluck('packageid')->toArray(),
            ];
        })->values();

        // Manual pagination for the grouped collection
        $perPage = 10;
        $page = request('page', 1);
        $paginated = new LengthAwarePaginator(
            $grouped->forPage($page, $perPage)->values(),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return Inertia::render('Master/Packages', [
            'promos' => $paginated,
            'packages' => Package::all(['packagename_id', 'packagename']),
            'menus' => Menu::all(['menuid', 'name', 'price', 'categoryid']),
            'addons' => Addon::all(['addonid', 'name', 'price', 'menuid', 'categoryid']),
            'freebies' => PromoFreebie::all(['freeid', 'name']),
            'branches' => Branch::all(['branchid', 'branchname']),
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'packagename' => 'required|string|max:255',
            'menuids' => 'required|array',
            'menuids.*' => 'exists:menus,menuid',
            'menu_qtys' => 'nullable|array',
            'menu_qtys.*' => 'nullable|integer|min:1',
            'menu_addons' => 'nullable|array',
            'menu_addons.*' => 'nullable|exists:addons,addonid',
            'freebie_ids' => 'nullable|array',
            'freebie_ids.*' => 'exists:freebies,freeid',
            'freebie_qtys' => 'nullable|array',
            'freebie_qtys.*' => 'nullable|integer|min:1',
            'branchids' => 'nullable|array',
            'branchids.*' => 'exists:branches,branchid',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:Available,Unavailable,Certain Period',
            'datefrom' => [
                'nullable', 'date',
                Rule::requiredIf(fn () => empty($request->days) && $request->status === 'Certain Period'),
            ],
            'dateto' => 'nullable|date|after_or_equal:datefrom',
            'timefrom' => 'nullable|date_format:H:i',
            'timeto' => 'nullable|date_format:H:i|after:timefrom|required_with:timefrom',
            'days' => 'nullable|array|prohibits:datefrom,dateto',
            'days.*' => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        ], [
            'datefrom.required_if' => 'Please provide either a date range or select active days.',
            'days.prohibits' => 'Specific days cannot be chosen when a date range is set.',
        ]);

        $package = Package::firstOrCreate(['packagename' => $request->packagename]);

        $addedCount = 0;
        $branchIdsToProcess = empty($request->branchids) ? [null] : $request->branchids;
        $menuQtys = $request->menu_qtys ?? [];
        $menuAddons = $request->menu_addons ?? [];
        $freebieIds = $request->freebie_ids ?? [];
        $freebieQtys = $request->freebie_qtys ?? [];

        foreach ($branchIdsToProcess as $branchId) {
            // Process Menus
            foreach ($request->menuids as $menuid) {
                // Prevent duplicate package-menu-branch links
                $exists = Promo::where('packagename_id', $package->packagename_id)
                    ->where('menuid', $menuid)
                    ->where('branchid', $branchId)
                    ->exists();

                if (! $exists) {
                    $promoLink = Promo::create([
                        'packagename_id' => $package->packagename_id,
                        'menuid' => $menuid,
                        'freeid' => null,
                        'branchid' => $branchId,
                        'qty' => $menuQtys[$menuid] ?? 1,
                        'price' => $request->price,
                        'status' => $request->status,
                        'datefrom' => $request->status === 'Certain Period' ? $request->datefrom : null,
                        'dateto' => $request->status === 'Certain Period' ? $request->dateto : null,
                        'timefrom' => $request->status === 'Certain Period' ? $request->timefrom : null,
                        'timeto' => $request->status === 'Certain Period' ? $request->timeto : null,
                        'days' => $request->status === 'Certain Period' ? $request->days : null,
                    ]);

                    // Attach Multiple Addons
                    if (! empty($menuAddons[$menuid])) {
                        $addonIds = is_array($menuAddons[$menuid]) ? $menuAddons[$menuid] : [$menuAddons[$menuid]];
                        $promoLink->addons()->attach($addonIds);
                    }

                    $addedCount++;
                }
            }

            // Process Freebies as separate rows with price 0
            foreach ($freebieIds as $freebieId) {
                $exists = Promo::where('packagename_id', $package->packagename_id)
                    ->where('menuid', null)
                    ->where('freeid', $freebieId)
                    ->where('branchid', $branchId)
                    ->exists();

                if (! $exists) {
                    Promo::create([
                        'packagename_id' => $package->packagename_id,
                        'menuid' => null,
                        'addonid' => null,
                        'freeid' => $freebieId,
                        'branchid' => $branchId,
                        'qty' => $freebieQtys[$freebieId] ?? 1,
                        'price' => 0,
                        'status' => $request->status,
                        'datefrom' => $request->status === 'Certain Period' ? $request->datefrom : null,
                        'dateto' => $request->status === 'Certain Period' ? $request->dateto : null,
                        'timefrom' => $request->status === 'Certain Period' ? $request->timefrom : null,
                        'timeto' => $request->status === 'Certain Period' ? $request->timeto : null,
                        'days' => $request->status === 'Certain Period' ? $request->days : null,
                    ]);
                    $addedCount++;
                }
            }
        }

        if ($addedCount === 0) {
            return back()->with('error', 'All selected items are already in this package for the selected branches.');
        }

        return back()->with('success', "$addedCount items added to package '{$package->packagename}' successfully.");
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'packagename' => 'required|string|max:255',
            'menuids' => 'required|array',
            'menuids.*' => 'exists:menus,menuid',
            'menu_qtys' => 'nullable|array',
            'menu_qtys.*' => 'nullable|integer|min:1',
            'menu_addons' => 'nullable|array',
            'menu_addons.*' => 'nullable|exists:addons,addonid',
            'freebie_ids' => 'nullable|array',
            'freebie_ids.*' => 'exists:freebies,freeid',
            'freebie_qtys' => 'nullable|array',
            'freebie_qtys.*' => 'nullable|integer|min:1',
            'branchids' => 'nullable|array',
            'branchids.*' => 'exists:branches,branchid',
            'all_ids' => 'required|array', // Original group IDs
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:Available,Unavailable,Certain Period',
            'datefrom' => 'nullable|date',
            'dateto' => 'nullable|date|after_or_equal:datefrom',
            'timefrom' => 'nullable|date_format:H:i',
            'timeto' => 'nullable|date_format:H:i|after:timefrom|required_with:timefrom',
            'days' => 'nullable|array',
            'days.*' => 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        ]);
        $package = Package::firstOrCreate(['packagename' => $request->packagename]);

        DB::transaction(function () use ($request, $package) {
            // Truly remove the old records being replaced in this update
            $deletedCount = Promo::whereIn('packageid', $request->all_ids)->forceDelete();

            Log::info("Updating Promotion Package: Removed $deletedCount old records, inserting new batch.");

            $branchIdsToProcess = empty($request->branchids) ? [null] : $request->branchids;
            $menuQtys = $request->menu_qtys ?? [];
            $menuAddons = $request->menu_addons ?? [];
            $freebieIds = $request->freebie_ids ?? [];
            $freebieQtys = $request->freebie_qtys ?? [];

            foreach ($branchIdsToProcess as $branchId) {
                // Re-create Menus
                foreach ($request->menuids as $menuid) {
                    $promoLink = Promo::create([
                        'packagename_id' => $package->packagename_id,
                        'menuid' => $menuid,
                        'freeid' => null,
                        'branchid' => $branchId,
                        'qty' => $menuQtys[$menuid] ?? 1,
                        'price' => $request->price,
                        'status' => $request->status,
                        'datefrom' => $request->status === 'Certain Period' ? $request->datefrom : null,
                        'dateto' => $request->status === 'Certain Period' ? $request->dateto : null,
                        'timefrom' => $request->status === 'Certain Period' ? $request->timefrom : null,
                        'timeto' => $request->status === 'Certain Period' ? $request->timeto : null,
                        'days' => $request->status === 'Certain Period' ? $request->days : null,
                    ]);

                    // Attach Multiple Addons
                    if (! empty($menuAddons[$menuid])) {
                        $addonIds = is_array($menuAddons[$menuid]) ? $menuAddons[$menuid] : [$menuAddons[$menuid]];
                        $promoLink->addons()->attach($addonIds);
                    }
                }

                // Re-create Freebies
                foreach ($freebieIds as $freebieId) {
                    Promo::create([
                        'packagename_id' => $package->packagename_id,
                        'menuid' => null,
                        'addonid' => null,
                        'freeid' => $freebieId,
                        'branchid' => $branchId,
                        'qty' => $freebieQtys[$freebieId] ?? 1,
                        'price' => 0,
                        'status' => $request->status,
                        'datefrom' => $request->status === 'Certain Period' ? $request->datefrom : null,
                        'dateto' => $request->status === 'Certain Period' ? $request->dateto : null,
                        'timefrom' => $request->status === 'Certain Period' ? $request->timefrom : null,
                        'timeto' => $request->status === 'Certain Period' ? $request->timeto : null,
                        'days' => $request->status === 'Certain Period' ? $request->days : null,
                    ]);
                }
            }
        });

        return back()->with('success', 'Promotion package updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        // Find the promo to identify the parent package name
        $promo = Promo::where('packageid', $id)->first();

        // If not found by single ID, try the first ID from the array if provided
        if (! $promo && $request->has('ids') && ! empty($request->ids)) {
            $promo = Promo::where('packageid', $request->ids[0])->first();
        }

        if ($promo && $promo->packagename_id) {
            $packageNameId = $promo->packagename_id;

            DB::transaction(function () use ($packageNameId) {
                // Delete all promos associated with this package name across all branches/variations
                Promo::where('packagename_id', $packageNameId)->delete();

                // Delete the package name record itself
                Package::where('packagename_id', $packageNameId)->delete();
            });

            return back()->with('success', 'Package and all its variations deleted successfully.');
        }

        return back()->with('error', 'Package not found.');
    }
}
