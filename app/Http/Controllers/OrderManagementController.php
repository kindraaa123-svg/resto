<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TableSeat;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['tableSeat', 'details.menu', 'details.product', 'details.package', 'details.addons.addon']);

        if ($request->filled('branch_id')) {
            $query->where('branchid', $request->branch_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('ordertime', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('ordertime', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('ordertime', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($order) {
                return [
                    'id' => $order->orderid,
                    'tableName' => $order->tableSeat ? $order->tableSeat->name : 'N/A',
                    'customerName' => $order->name,
                    'status' => $order->status,
                    'orderTime' => $order->ordertime,
                    'totalPrice' => (float) $order->totalprice,
                    'promoName' => $order->applied_promotion_name,
                    'promoDiscount' => (float) $order->applied_promotion_discount,
                    'items' => $order->details->map(function ($detail) {
                        $name = 'Unknown';
                        $price = 0;
                        
                        if ($detail->menu) {
                            $name = $detail->menu->name;
                            $price = $detail->menu->price;
                        } elseif ($detail->product) {
                            $name = $detail->product->name;
                            $price = $detail->product->price;
                        }

                        return [
                            'name' => $name,
                            'price' => (float) $price,
                            'quantity' => $detail->quantity,
                            'isFree' => (bool) $detail->is_free,
                            'packageName' => $detail->package ? $detail->package->packagename : null,
                            'addons' => $detail->addons->map(fn ($a) => $a->addon ? $a->addon->name : ''),
                        ];
                    }),
                ];
            });

        $branches = Branch::orderBy('branchname', 'asc')->get();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'branches' => $branches,
            'filters' => $request->only(['branch_id', 'date_from', 'date_to', 'status']),
        ]);
    }

    public function setTableVacant(int $tableId)
    {
        $table = TableSeat::findOrFail($tableId);

        return back()->with('success', "Table {$table->name} is now vacant.");
    }

    public function destroy(Request $request, int $id)
    {
        $order = Order::findOrFail($id);
        
        // Log who authorized the cancellation
        $managerId = $request->manager_id;
        $manager = \App\Models\User::find($managerId);
        
        $order->update([
            'status' => 'Cancelled',
        ]);

        // Optional: Add activity log for the cancellation
        activity()
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->withProperty('authorized_by', $manager ? $manager->name : 'Unknown')
            ->log('Order cancelled via Face Approval');

        return back()->with('success', "Order #{$id} has been cancelled.");
    }
}
