<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Operational;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OperationalController extends Controller
{
    public function index()
    {
        $operationals = QueryBuilder::for(Operational::class)
            ->with('branch')
            ->allowedFilters([
                'name',
                'category',
                'branchid',
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where('name', 'like', "%{$value}%");
                }),
            ])
            ->defaultSort('-created_at')
            ->paginate(15)
            ->through(fn ($op) => array_merge($op->toArray(), [
                'impact' => $op->getDeletionImpact(),
            ]))
            ->withQueryString();

        $branches = Branch::all();

        return Inertia::render('Master/Operationals', [
            'operationals' => $operationals,
            'branches' => $branches,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branchid' => 'required|exists:branches,branchid',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|in:Electricity,Water,Others',
            'created_at' => 'nullable|date',
        ]);

        $date = $request->created_at ? Carbon::parse($request->created_at) : now();

        Operational::create([
            'branchid' => $request->branchid,
            'name' => $request->name,
            'amount' => $request->amount,
            'category' => $request->category,
            'date' => $date->format('Y-m-d'),
            'created_at' => $date,
        ]);

        return back()->with('success', 'Operational record created successfully.');
    }

    public function update(Request $request, $id)
    {
        $operational = Operational::findOrFail($id);

        $request->validate([
            'branchid' => 'required|exists:branches,branchid',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|in:Electricity,Water,Others',
            'created_at' => 'nullable|date',
        ]);

        $date = $request->created_at ? Carbon::parse($request->created_at) : $operational->created_at;

        $operational->update([
            'branchid' => $request->branchid,
            'name' => $request->name,
            'amount' => $request->amount,
            'category' => $request->category,
            'date' => $date->format('Y-m-d'),
            'created_at' => $date,
        ]);

        return back()->with('success', 'Operational record updated successfully.');
    }

    public function destroy($id)
    {
        $operational = Operational::findOrFail($id);
        $operational->delete();

        return back()->with('success', 'Operational record deleted successfully.');
    }
}
