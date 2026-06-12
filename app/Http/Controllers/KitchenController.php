<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KitchenController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);

        // Get orders that are currently being cooked or pending
        $pendingOrdersQuery = Order::with('tableSeat')
            ->where(function($q) {
                $q->whereIn('status', ['Pending', 'Cooking', ''])
                  ->orWhereNull('status');
            })
            ->orderBy('ordertime', 'asc');

        $totalPending = $pendingOrdersQuery->count();
        $pendingOrders = $pendingOrdersQuery->limit($limit)->get()
            ->map(function ($order) {
                // Fetch details including package names if available
                $details = DB::table('order_details')
                    ->leftJoin('menus', 'order_details.menuid', '=', 'menus.menuid')
                    ->leftJoin('products', 'order_details.productid', '=', 'products.productid')
                    ->leftJoin('package_names', 'order_details.packagename_id', '=', 'package_names.packagename_id')
                    ->where('order_details.orderid', $order->orderid)
                    ->select(
                        'order_details.detailorderid',
                        'order_details.quantity',
                        'order_details.note',
                        'order_details.packagename_id',
                        'order_details.is_free',
                        'menus.name as menu_name',
                        'products.productname as product_name',
                        'package_names.packagename'
                    )
                    ->get();

                $processedItems = [];
                $packageGroups = [];

                foreach ($details as $detail) {
                    $name = $detail->menu_name ?? $detail->product_name ?? 'Unknown Item';
                    
                    if ($detail->is_free) {
                        $name .= ' (FREE)';
                    }

                    $addons = DB::table('order_addons')
                        ->join('addons', 'order_addons.addonid', '=', 'addons.addonid')
                        ->where('order_addons.detailorderid', $detail->detailorderid)
                        ->select('addons.name')
                        ->get();

                    if ($detail->packagename_id) {
                        if (!isset($packageGroups[$detail->packagename_id])) {
                            $packageGroups[$detail->packagename_id] = [
                                'id' => 'pkg-' . $detail->packagename_id . '-' . $order->orderid,
                                'name' => $detail->packagename ?? 'PROMO PACKAGE',
                                'quantity' => 1,
                                'note' => $detail->note,
                                'is_package' => true,
                                'sub_items' => []
                            ];
                        }

                        if (empty($packageGroups[$detail->packagename_id]['note']) && !empty($detail->note)) {  
                            $packageGroups[$detail->packagename_id]['note'] = $detail->note;
                        }

                        $packageGroups[$detail->packagename_id]['sub_items'][] = [
                            'name' => $name,
                            'quantity' => $detail->quantity,
                            'addons' => $addons->pluck('name')->toArray(),
                        ];
                    } else {
                        $processedItems[] = [
                            'id' => $detail->detailorderid,
                            'name' => $name,
                            'quantity' => $detail->quantity,
                            'note' => $detail->note,
                            'is_package' => false,
                            'addons' => $addons->pluck('name')->toArray(),
                        ];
                    }
                }

                foreach ($packageGroups as $pkg) {
                    $processedItems[] = $pkg;
                }

                return [
                    'id' => $order->orderid,
                    'name' => $order->name,
                    'tableName' => $order->tableSeat ? $order->tableSeat->name : 'Walk-in',
                    'orderTime' => $order->ordertime,
                    'orderType' => $order->ordertype,
                    'status' => $order->status ?: 'Pending',
                    'note' => $order->note,
                    'items' => $processedItems,
                ];
            });

        // Get orders that are served today
        $completedOrders = Order::with('tableSeat')
            ->where('status', 'Served')
            ->where(function($query) {
                $query->whereDate('ordertime', today())
                      ->orWhere(function($q) {
                          $q->whereNotNull('completiontime')
                            ->where('completiontime', '!=', 0)
                            ->where('completiontime', '>=', now()->startOfDay()->timestamp);
                      });
            })
            ->orderBy('completiontime', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->orderid,
                    'name' => $order->name,
                    'tableName' => $order->tableSeat ? $order->tableSeat->name : 'Walk-in',
                    'orderTime' => $order->ordertime,
                    'completionTime' => $order->completiontime ? date('Y-m-d H:i:s', $order->completiontime) : $order->ordertime,
                    'orderType' => $order->ordertype,
                    'status' => $order->status,
                ];
            });

        return Inertia::render('Kitchen/Index', [
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
            'totalPending' => $totalPending,
            'currentLimit' => (int) $limit,
        ]);
    }

    public function start(Request $request, $orderid)
    {
        $order = Order::findOrFail($orderid);
        
        if (in_array($order->status, ['Pending', ''])) {
            $order->update(['status' => 'Cooking']);
            return back()->with('success', 'Cooking started!');
        }

        return back()->with('error', 'Cannot start this order.');
    }

    public function complete(Request $request, $orderid)
    {
        $order = Order::findOrFail($orderid);

        if ($order->status === 'Cooking') {
            DB::beginTransaction();
            try {
                // 1. Deduct Inventory
                $this->deductInventory($order->orderid);

                // 2. Update Status
                $order->update([
                    'status' => 'Served',
                    'completiontime' => now()->timestamp,
                ]);

                DB::commit();

                return back()->with('success', 'Order marked as Served!');
            } catch (\Exception $e) {
                DB::rollBack();

                return back()->with('error', 'Error reducing stock: '.$e->getMessage());
            }
        }

        return back()->with('error', 'Cannot complete this order.');
    }

    private function deductInventory($orderId)
    {
        // TODO: Implement actual ingredient/product deduction based on recipes
        // This is a placeholder to remind that stock management is enabled.
        \Log::info("Inventory deduction triggered for Order #{$orderId}");
    }
}
