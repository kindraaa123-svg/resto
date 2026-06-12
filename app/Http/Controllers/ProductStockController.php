<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductLog;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ProductStockController extends Controller
{
    public function index()
    {
        $stocks = QueryBuilder::for(ProductStock::class)
            ->with(['product', 'branch'])
            ->allowedFilters(['product.productname', 'branch.branchname'])
            ->defaultSort('-updated_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Products/Stocks', [
            'stocks' => $stocks,
            'products' => Product::all(['productid', 'productname']),
            'branches' => Branch::all(['branchid', 'branchname']),
            'existingStockIds' => ProductStock::select('productid', 'branchid')->get(),
        ]);
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'productid' => 'required|exists:products,productid',
            'branchid' => 'required|exists:branches,branchid',
            'qty' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Update/Create Stock
            $stock = ProductStock::firstOrCreate(
                ['productid' => $request->productid, 'branchid' => $request->branchid],
                ['stock' => 0]
            );

            $stock->increment('stock', (int) $request->qty);

            // 2. Insert Log
            ProductLog::create([
                'productid' => $request->productid,
                'branchid' => $request->branchid,
                'type' => 'IN',
                'qty' => (int) $request->qty,
                'balance' => $stock->fresh()->stock,
                'price' => $request->price ?? 0,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Stock added successfully.');
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'productid' => 'required|exists:products,productid',
            'branchid' => 'required|exists:branches,branchid',
            'physical_qty' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $stock = ProductStock::firstOrCreate(
                ['productid' => $request->productid, 'branchid' => $request->branchid],
                ['stock' => 0]
            );

            $currentStock = (int) $stock->stock;
            $diff = (int) $request->physical_qty - $currentStock;

            if ($diff == 0) {
                return;
            }

            // Update to physical qty
            $stock->update(['stock' => (int) $request->physical_qty]);

            // Log the difference
            ProductLog::create([
                'productid' => $request->productid,
                'branchid' => $request->branchid,
                'type' => $diff > 0 ? 'IN' : 'OUT',
                'qty' => abs($diff),
                'balance' => (int) $request->physical_qty,
                'price' => 0,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Stock adjusted based on physical count.');
    }

    public function destroy($id)
    {
        $stock = ProductStock::findOrFail($id);
        $stock->delete();

        return back()->with('success', 'Stock record deleted successfully.');
    }
}
