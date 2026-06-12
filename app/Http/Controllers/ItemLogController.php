<?php

namespace App\Http\Controllers;

use App\Helpers\UnitConverter;
use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemLog;
use App\Models\ItemStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ItemLogController extends Controller
{
    public function index(Request $request)
    {
        $userBranchId = auth()->user()->branchid;

        $itemLogs = QueryBuilder::for(ItemLog::class)
            ->with(['item', 'branch'])
            ->when($userBranchId, fn ($q) => $userBranchId === null ? $q : $q->where('branchid', $userBranchId))
            ->allowedFilters(['type', 'branchid'])
            ->defaultSort('-created_at')
            ->paginate(15)
            ->through(fn ($log) => [
                'itemlogid' => $log->itemlogid,
                'itemid' => $log->itemid,
                'item_name' => $log->item->itemname ?? 'Unknown',
                'branchid' => $log->branchid,
                'branch_name' => $log->branch->branchname ?? 'Unknown',
                'type' => $log->type,
                'qty' => $log->qty,
                'balance' => $log->balance,
                'unit' => $log->unit,
                'display_qty' => UnitConverter::format($log->qty, $log->unit),
                'display_balance' => UnitConverter::format($log->balance, $log->unit),
                'price' => $log->price,
                'total_price' => (float) $log->qty * (float) $log->price,
                'created_at' => $log->created_at,
            ]);

        return Inertia::render('Master/ItemLogs', [
            'itemLogs' => $itemLogs,
            'items' => Item::all(['itemid', 'itemname']),
            'branches' => Branch::all(['branchid', 'branchname']),
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'itemid' => 'required|exists:items,itemid',
            'branchid' => 'required|exists:branches,branchid',
            'type' => 'required|in:IN,OUT',
            'qty' => 'required|numeric|min:0',
            'unit' => 'required|in:Unit,Pcs',
            'price' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            ItemLog::create($request->all());

            $stock = ItemStock::firstOrNew([
                'itemid' => $request->itemid,
                'branchid' => $request->branchid,
            ]);

            if ($request->type === 'IN') {
                $stock->qty += $request->qty;
            } else {
                $stock->qty -= $request->qty;
            }

            $stock->unit = $request->unit;
            $stock->save();
        });

        return back()->with('success', 'Log entry created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'itemid' => 'required|exists:items,itemid',
            'branchid' => 'required|exists:branches,branchid',
            'type' => 'required|in:IN,OUT',
            'qty' => 'required|numeric|min:0',
            'unit' => 'required|in:Unit,Pcs',
            'price' => 'nullable|numeric|min:0',
        ]);

        $log = ItemLog::findOrFail($id);

        DB::transaction(function () use ($request, $log) {
            // Revert old stock
            $oldStock = ItemStock::where('itemid', $log->itemid)->where('branchid', $log->branchid)->first();
            if ($oldStock) {
                if ($log->type === 'IN') {
                    $oldStock->qty -= $log->qty;
                } else {
                    $oldStock->qty += $log->qty;
                }
                $oldStock->save();
            }

            // Update log
            $log->update($request->all());

            // Apply new stock
            $newStock = ItemStock::firstOrNew([
                'itemid' => $request->itemid,
                'branchid' => $request->branchid,
            ]);
            if ($request->type === 'IN') {
                $newStock->qty += $request->qty;
            } else {
                $newStock->qty -= $request->qty;
            }
            $newStock->unit = $request->unit;
            $newStock->save();
        });

        return back()->with('success', 'Log entry updated successfully.');
    }

    public function destroy($id)
    {
        $log = ItemLog::findOrFail($id);

        DB::transaction(function () use ($log) {
            // Revert stock
            $stock = ItemStock::where('itemid', $log->itemid)->where('branchid', $log->branchid)->first();
            if ($stock) {
                if ($log->type === 'IN') {
                    $stock->qty -= $log->qty;
                } else {
                    $stock->qty += $log->qty;
                }
                $stock->save();
            }
            $log->delete();
        });

        return back()->with('success', 'Log entry deleted successfully.');
    }
}
