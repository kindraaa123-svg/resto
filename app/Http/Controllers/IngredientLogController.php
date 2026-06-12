<?php

namespace App\Http\Controllers;

use App\Helpers\UnitConverter;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\IngredientLog;
use App\Models\IngredientStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IngredientLogController extends Controller
{
    public function index()
    {
        $userBranchId = auth()->user()->branchid;

        $query = QueryBuilder::for(IngredientLog::class)
            ->with(['ingredient', 'branch']);

        if ($userBranchId) {
            $query->where('branchid', $userBranchId);
        }

        $ingredientLogs = $query->allowedFilters([
            AllowedFilter::exact('branchid'),
            AllowedFilter::exact('type'),
            AllowedFilter::callback('search', function ($query, $value) {
                $query->whereHas('ingredient', function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%");
                });
            }),
        ])
            ->defaultSort('-created_at') // Sort by newest first
            ->paginate(15)
            ->through(function ($log) {
                return [
                    'ingredientlogid' => $log->ingredientlogid,
                    'ingredientid' => $log->ingredientid,
                    'ingredient_name' => $log->ingredient->name,
                    'branchid' => $log->branchid,
                    'branch_name' => $log->branch->branchname,
                    'type' => $log->type,
                    'qty' => $log->qty,
                    'balance' => $log->balance,
                    'unit' => $log->unit,
                    'display_qty' => UnitConverter::format($log->qty, $log->unit),
                    'display_balance' => UnitConverter::format($log->balance, $log->unit),
                    'total_price' => (float) $log->qty * (float) $log->price,
                    'created_at' => $log->created_at,
                ];
            })
            ->withQueryString();

        $ingredients = Ingredient::orderBy('name')->get();
        $branches = $userBranchId
            ? Branch::where('branchid', $userBranchId)->get()
            : Branch::all();

        return Inertia::render('Master/IngredientLogs', [
            'ingredientLogs' => $ingredientLogs,
            'ingredients' => $ingredients,
            'branches' => $branches,
            'filters' => request('filter', (object) []),
        ]);
    }

    public function store(Request $request)
    {
        $userBranchId = auth()->user()->branchid;

        // Force branchid if user has one
        if ($userBranchId) {
            $request->merge(['branchid' => $userBranchId]);
        }

        $request->validate([
            'ingredientid' => 'required|exists:ingredients,ingredientid',
            'branchid' => 'required|exists:branches,branchid',
            'type' => 'required|in:IN,OUT',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'required|in:Kg,Gr,Lt,mL,Pcs',
            'total_price' => 'required_if:type,IN|nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $baseQty = UnitConverter::toBase($request->qty, $request->unit);
            $baseUnit = UnitConverter::getBaseUnit($request->unit);
            $perUnitPrice = $request->type === 'IN' ? ($request->total_price / $baseQty) : 0;

            $log = IngredientLog::create([
                'ingredientid' => $request->ingredientid,
                'branchid' => $request->branchid,
                'type' => $request->type,
                'qty' => $baseQty,
                'balance' => 0, // Placeholder
                'unit' => $baseUnit,
                'price' => $perUnitPrice,
                'created_at' => now(),
            ]);

            // Sync with IngredientStock
            $stock = IngredientStock::firstOrNew([
                'ingredientid' => $request->ingredientid,
                'branchid' => $request->branchid,
            ]);

            $stock->unit = $baseUnit;
            $multiplier = $request->type === 'IN' ? 1 : -1;
            $stock->qty = (float) ($stock->qty ?? 0) + ($baseQty * $multiplier);
            $stock->save();

            // Update balance in log
            $log->update(['balance' => $stock->qty]);
        });

        return back()->with('success', 'Inventory log created and stock updated successfully.');
    }

    public function update(Request $request, $id)
    {
        $userBranchId = auth()->user()->branchid;
        $log = IngredientLog::findOrFail($id);

        // Security check: user can only update their own branch logs
        if ($userBranchId && $log->branchid !== $userBranchId) {
            abort(403, 'Unauthorized action.');
        }

        // Force branchid if user has one
        if ($userBranchId) {
            $request->merge(['branchid' => $userBranchId]);
        }

        $request->validate([
            'ingredientid' => 'required|exists:ingredients,ingredientid',
            'branchid' => 'required|exists:branches,branchid',
            'type' => 'required|in:IN,OUT',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'required|in:Kg,Gr,Lt,mL,Pcs',
            'total_price' => 'required_if:type,IN|nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $log) {
            // Reverse old stock effect (log->qty is in base unit)
            $oldStock = IngredientStock::where([
                'ingredientid' => $log->ingredientid,
                'branchid' => $log->branchid,
            ])->first();

            if ($oldStock) {
                $oldMultiplier = $log->type === 'IN' ? -1 : 1;
                $oldStock->qty = (float) ($oldStock->qty ?? 0) + ($log->qty * $oldMultiplier);
                $oldStock->save();
            }

            // Apply new stock effect
            $baseQty = UnitConverter::toBase($request->qty, $request->unit);
            $baseUnit = UnitConverter::getBaseUnit($request->unit);
            $perUnitPrice = $request->type === 'IN' ? ($request->total_price / $baseQty) : 0;

            $newStock = IngredientStock::firstOrNew([
                'ingredientid' => $request->ingredientid,
                'branchid' => $request->branchid,
            ]);

            $newStock->unit = $baseUnit;
            $newMultiplier = $request->type === 'IN' ? 1 : -1;
            $newStock->qty = (float) ($newStock->qty ?? 0) + ($baseQty * $newMultiplier);
            $newStock->save();

            // Update log
            $log->update([
                'ingredientid' => $request->ingredientid,
                'branchid' => $request->branchid,
                'type' => $request->type,
                'qty' => $baseQty,
                'balance' => $newStock->qty,
                'unit' => $baseUnit,
                'price' => $perUnitPrice,
            ]);
        });

        return back()->with('success', 'Inventory log updated and stock adjusted successfully.');
    }

    public function destroy($id)
    {
        $log = IngredientLog::findOrFail($id);

        DB::transaction(function () use ($log) {
            // Reverse stock effect before deleting (log->qty is in base unit)
            $stock = IngredientStock::where([
                'ingredientid' => $log->ingredientid,
                'branchid' => $log->branchid,
            ])->first();

            if ($stock) {
                $multiplier = $log->type === 'IN' ? -1 : 1;
                $stock->qty = (float) ($stock->qty ?? 0) + ($log->qty * $multiplier);
                $stock->save();
            }

            $log->delete();
        });

        return back()->with('success', 'Inventory log deleted and stock reversed successfully.');
    }
}
