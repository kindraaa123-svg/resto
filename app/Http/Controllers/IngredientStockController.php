<?php

namespace App\Http\Controllers;

use App\Helpers\UnitConverter;
use App\Models\Branch;
use App\Models\DisposalIngredient;
use App\Models\Ingredient;
use App\Models\IngredientStock;
use App\Models\RequestIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IngredientStockController extends Controller
{
    public function index()
    {
        $userBranchId = auth()->user()->branchid;

        $query = QueryBuilder::for(IngredientStock::class)
            ->with(['ingredient', 'branch']);

        if ($userBranchId) {
            $query->where('branchid', $userBranchId);
        }

        $ingredientStocks = $query->allowedFilters([
            AllowedFilter::exact('branchid'),
            AllowedFilter::callback('search', function ($query, $value) {
                $query->whereHas('ingredient', function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%");
                });
            }),
        ])
            ->defaultSort('ingredientstock_id')
            ->paginate(15)
            ->through(function ($stock) {
                return [
                    'stockid' => $stock->ingredientstock_id,
                    'ingredientid' => $stock->ingredientid,
                    'ingredient_name' => $stock->ingredient->name,
                    'branch_name' => $stock->branch->branchname,
                    'branchid' => $stock->branchid,
                    'qty' => $stock->qty,
                    'unit' => $stock->unit,
                    'display_qty' => UnitConverter::format($stock->qty, $stock->unit),
                ];
            })
            ->withQueryString();

        $ingredients = Ingredient::orderBy('name')->get();
        $branches = $userBranchId
            ? Branch::where('branchid', $userBranchId)->get()
            : Branch::all();

        $pendingRequests = RequestIngredient::with(['ingredient', 'branch', 'requester'])
            ->where('status', 'Pending')
            ->when($userBranchId, fn ($q) => $q->where('branchid', $userBranchId))
            ->get();

        $pendingDisposals = DisposalIngredient::with(['ingredient', 'branch', 'creator'])
            ->where('status', 'Pending')
            ->when($userBranchId, fn ($q) => $q->where('branchid', $userBranchId))
            ->get();

        return Inertia::render('Master/IngredientStocks', [
            'ingredientStocks' => $ingredientStocks,
            'ingredients' => $ingredients,
            'branches' => $branches,
            'existingStockIds' => IngredientStock::select('ingredientid', 'branchid')->get(),
            'pendingRequests' => $pendingRequests,
            'pendingDisposals' => $pendingDisposals,
            'filters' => request('filter', (object) []),
        ]);
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'ingredientid' => 'required|exists:ingredients,ingredientid',
            'branchid' => 'required|exists:branches,branchid',
            'qty' => 'required|numeric|min:0.01',
            'total_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $ingredient = Ingredient::findOrFail($request->ingredientid);
            $baseQty = UnitConverter::toBase($request->qty, $ingredient->unit);
            $baseUnit = UnitConverter::getBaseUnit($ingredient->unit);

            // 1. Update/Create Stock
            $stock = IngredientStock::firstOrCreate(
                ['ingredientid' => $request->ingredientid, 'branchid' => $request->branchid],
                ['qty' => 0, 'unit' => $baseUnit]
            );

            $stock->increment('qty', $baseQty);

            // 2. Insert Log
            DB::table('ingredient_logs')->insert([
                'ingredientid' => $request->ingredientid,
                'branchid' => $request->branchid,
                'type' => 'IN',
                'qty' => $baseQty,
                'balance' => $stock->fresh()->qty,
                'unit' => $baseUnit,
                'price' => (float) $request->total_price / (float) $baseQty,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Stock added successfully.');
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'ingredientid' => 'required|exists:ingredients,ingredientid',
            'branchid' => 'required|exists:branches,branchid',
            'physical_qty' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $ingredient = Ingredient::findOrFail($request->ingredientid);
            $physicalBaseQty = UnitConverter::toBase($request->physical_qty, $ingredient->unit);
            $baseUnit = UnitConverter::getBaseUnit($ingredient->unit);

            $stock = IngredientStock::firstOrCreate(
                ['ingredientid' => $request->ingredientid, 'branchid' => $request->branchid],
                ['qty' => 0, 'unit' => $baseUnit]
            );

            $currentStock = (float) $stock->qty;
            $diff = $physicalBaseQty - $currentStock;

            if ($diff == 0) {
                return;
            }

            // Update to physical qty
            $stock->update(['qty' => $physicalBaseQty]);

            // Log the difference
            DB::table('ingredient_logs')->insert([
                'ingredientid' => $request->ingredientid,
                'branchid' => $request->branchid,
                'type' => $diff > 0 ? 'IN' : 'OUT',
                'qty' => abs($diff),
                'balance' => $physicalBaseQty,
                'unit' => $baseUnit,
                'price' => 0,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Stock adjusted based on physical count.');
    }

    public function requestIngredient(Request $request)
    {
        $request->validate([
            'ingredientid' => 'required|exists:ingredients,ingredientid',
            'branchid' => 'required|exists:branches,branchid',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'required|string',
            'description' => 'nullable|string',
        ]);

        RequestIngredient::create([
            'ingredientid' => $request->ingredientid,
            'branchid' => $request->branchid,
            'qty' => $request->qty,
            'unit' => $request->unit,
            'description' => $request->description,
            'status' => 'Pending',
            'requested_by' => auth()->id(),
        ]);

        return back()->with('success', 'Ingredient request submitted successfully.');
    }

    public function approveRequest(Request $request, $id)
    {
        $req = RequestIngredient::findOrFail($id);

        DB::transaction(function () use ($req) {
            $req->update([
                'status' => 'Approved',
                'approved_by' => auth()->id(),
            ]);

            $ingredient = Ingredient::findOrFail($req->ingredientid);
            $baseQty = UnitConverter::toBase($req->qty, $req->unit);
            $baseUnit = UnitConverter::getBaseUnit($ingredient->unit);

            // Update Stock
            $stock = IngredientStock::firstOrCreate(
                ['ingredientid' => $req->ingredientid, 'branchid' => $req->branchid],
                ['qty' => 0, 'unit' => $baseUnit]
            );

            $stock->increment('qty', $baseQty);

            // Create Log
            DB::table('ingredient_logs')->insert([
                'ingredientid' => $req->ingredientid,
                'branchid' => $req->branchid,
                'type' => 'IN',
                'qty' => $baseQty,
                'balance' => $stock->fresh()->qty,
                'unit' => $baseUnit,
                'price' => 0,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Request approved and stock updated.');
    }

    public function rejectRequest(Request $request, $id)
    {
        $req = RequestIngredient::findOrFail($id);
        $req->update([
            'status' => 'Rejected',
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Request rejected.');
    }

    public function approveDisposal(Request $request, $id)
    {
        $dispose = DisposalIngredient::findOrFail($id);

        DB::transaction(function () use ($dispose) {
            $dispose->update([
                'status' => 'Approved',
                'approved_by' => auth()->id(),
            ]);

            $ingredient = Ingredient::findOrFail($dispose->ingredientid);
            $baseQty = UnitConverter::toBase($dispose->qty, $dispose->unit);
            $baseUnit = UnitConverter::getBaseUnit($ingredient->unit);

            // Update Stock
            $stock = IngredientStock::where('ingredientid', $dispose->ingredientid)
                ->where('branchid', $dispose->branchid)
                ->first();

            if ($stock) {
                $stock->decrement('qty', $baseQty);

                // Create Log
                DB::table('ingredient_logs')->insert([
                    'ingredientid' => $dispose->ingredientid,
                    'branchid' => $dispose->branchid,
                    'type' => 'OUT',
                    'qty' => $baseQty,
                    'balance' => $stock->fresh()->qty,
                    'unit' => $baseUnit,
                    'price' => 0,
                    'created_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Disposal approved and stock updated.');
    }

    public function destroy($id)
    {
        $stock = IngredientStock::findOrFail($id);
        $stock->delete();

        return back()->with('success', 'Ingredient stock record deleted successfully.');
    }
}
