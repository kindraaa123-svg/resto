<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DisposalItem;
use App\Models\Item;
use App\Models\ItemLog;
use App\Models\ItemStock;
use App\Models\RequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ItemStockController extends Controller
{
    public function index()
    {
        $userBranchId = auth()->user()->branchid;

        $itemStocks = QueryBuilder::for(ItemStock::class)
            ->with(['item', 'branch'])
            ->when($userBranchId, fn ($q) => $q->where('branchid', $userBranchId))
            ->defaultSort('itemname')
            ->join('items', 'item_stocks.itemid', '=', 'items.itemid')
            ->select('item_stocks.*')
            ->paginate(15)
            ->through(fn ($stock) => [
                'stockid' => $stock->itemstock_id,
                'itemid' => $stock->itemid,
                'item_name' => $stock->item->itemname ?? 'Unknown',
                'branchid' => $stock->branchid,
                'branch_name' => $stock->branch->branchname ?? 'Unknown',
                'qty' => (int) $stock->qty,
                'unit' => 'Pcs',
            ])
            ->withQueryString();

        return Inertia::render('Master/ItemStocks', [
            'itemStocks' => $itemStocks,
            'items' => Item::all(['itemid', 'itemname']),
            'branches' => Branch::all(['branchid', 'branchname']),
            'existingStockIds' => ItemStock::select('itemid', 'branchid')->get(),
        ]);
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'itemid' => 'required|exists:items,itemid',
            'branchid' => 'required|exists:branches,branchid',
            'qty' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Update/Create Stock
            $stock = ItemStock::firstOrCreate(
                ['itemid' => $request->itemid, 'branchid' => $request->branchid],
                ['qty' => 0]
            );

            $stock->increment('qty', (int) $request->qty);

            // 2. Insert Log
            ItemLog::create([
                'itemid' => $request->itemid,
                'branchid' => $request->branchid,
                'type' => 'IN',
                'qty' => (int) $request->qty,
                'balance' => $stock->fresh()->qty,
                'unit' => 'Pcs',
                'price' => $request->price,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Stock added successfully.');
    }

    public function adjustStock(Request $request)
    {
        $request->validate([
            'itemid' => 'required|exists:items,itemid',
            'branchid' => 'required|exists:branches,branchid',
            'physical_qty' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $stock = ItemStock::firstOrCreate(
                ['itemid' => $request->itemid, 'branchid' => $request->branchid],
                ['qty' => 0]
            );

            $currentStock = (int) $stock->qty;
            $diff = (int) $request->physical_qty - $currentStock;

            if ($diff == 0) {
                return;
            }

            // Update to physical qty
            $stock->update(['qty' => (int) $request->physical_qty]);

            // Log the difference
            ItemLog::create([
                'itemid' => $request->itemid,
                'branchid' => $request->branchid,
                'type' => $diff > 0 ? 'IN' : 'OUT',
                'qty' => abs($diff),
                'balance' => (int) $request->physical_qty,
                'unit' => 'Pcs',
                'price' => 0,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Stock adjusted based on physical count.');
    }

    public function destroy($id)
    {
        $stock = ItemStock::findOrFail($id);
        $stock->delete();

        return back()->with('success', 'Stock record deleted successfully.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'itemid' => 'required|exists:items,itemid',
            'branchid' => 'required|exists:branches,branchid',
            'qty' => 'required|numeric',
            'unit' => 'required|in:Unit,Pcs',
        ]);

        ItemStock::updateOrCreate(
            ['itemid' => $request->itemid, 'branchid' => $request->branchid],
            ['qty' => $request->qty, 'unit' => $request->unit]
        );

        return back()->with('success', 'Item stock updated successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'qty' => 'required|numeric',
            'unit' => 'required|in:Unit,Pcs',
        ]);

        $stock = ItemStock::findOrFail($id);
        $stock->update($request->only(['qty', 'unit']));

        return back()->with('success', 'Item stock updated successfully.');
    }

    public function requestItem(Request $request, $stockid)
    {
        $request->validate([
            'qty' => 'required|numeric|min:1',
            'description' => 'required|string',
        ]);

        $stock = ItemStock::findOrFail($stockid);

        RequestItem::create([
            'itemid' => $stock->itemid,
            'description' => $request->description,
            'qty' => $request->qty,
            'status' => 'Pending',
            'requested_by' => auth()->id(),
        ]);

        return back()->with('success', 'Item request submitted successfully.');
    }

    public function disposeItem(Request $request, $stockid)
    {
        $request->validate([
            'qty' => 'required|numeric|min:1|lte:'.ItemStock::findOrFail($stockid)->qty,
            'reason' => 'required|string',
            'evidence' => 'required|image|max:2048', // Max 2MB image
        ]);

        $path = $request->file('evidence')->store('disposals', 'public');
        $evidenceUrl = asset('storage/'.$path);

        DisposalItem::create([
            'itemstock_id' => $stockid,
            'qty' => $request->qty,
            'reason' => $request->reason,
            'evidence' => $evidenceUrl,
            'status' => 'Pending',
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Disposal report submitted successfully.');
    }
}
