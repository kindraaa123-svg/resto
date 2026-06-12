<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

use Spatie\QueryBuilder\AllowedFilter;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = QueryBuilder::for(Branch::class)
            ->with(['province', 'city', 'district', 'village'])
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('branchname', 'like', "%{$value}%")
                          ->orWhere('branchid', 'like', "%{$value}%")
                          ->orWhere('detail_address', 'like', "%{$value}%");
                    });
                }),
            ])
            ->defaultSort('-branchid')
            ->paginate($request->per_page ?? 10)
            ->withQueryString()
            ->through(fn ($b) => [
                'branchid' => $b->branchid,
                'branchname' => $b->branchname,
                'detail_address' => $b->detail_address,
                'impact' => $b->getDeletionImpact(),
                'provincesid' => $b->provincesid,
                'citiesid' => $b->citiesid,
                'districtsid' => $b->districtsid,
                'villagesid' => $b->villagesid,
                'longitude' => $b->longitude,
                                'latitude' => $b->latitude,
                'province' => $b->province,
                'city' => $b->city,
                'district' => $b->district,
                'village' => $b->village,
            ]);

        $provinces = Province::all();

        return Inertia::render('Master/Branches', [
            'branches' => $branches,
            'provinces' => $provinces,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branchname' => 'required|string|max:255',
            'provincesid' => 'required|exists:provinces,provinceid',
            'citiesid' => 'required|exists:cities,cityid',
            'districtsid' => 'required|exists:districts,districtid',
            'villagesid' => 'required|exists:villages,villageid',
            'detail_address' => 'required|string|max:255',
            'longitude' => 'required|string|max:255',
            'latitude' => 'required|string|max:255',
        ]);

        Branch::create($request->all());

        return back()->with('success', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'branchname' => 'required|string|max:255',
            'provincesid' => 'required|exists:provinces,provinceid',
            'citiesid' => 'required|exists:cities,cityid',
            'districtsid' => 'required|exists:districts,districtid',
            'villagesid' => 'required|exists:villages,villageid',
            'detail_address' => 'required|string|max:255',
            'longitude' => 'required|string|max:255',
            'latitude' => 'required|string|max:255',
        ]);

        $branch->update($request->all());

        return back()->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return back()->with('success', 'Branch deleted successfully.');
    }

    public function getCities(Province $province)
    {
        return response()->json(City::where('provinceid', $province->provinceid)->get());
    }

    public function getDistricts(City $city)
    {
        return response()->json(District::where('cityid', $city->cityid)->get());
    }

    public function getVillages(District $district)
    {
        return response()->json(Village::where('districtid', $district->districtid)->get());
    }
}
