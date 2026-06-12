<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentProcessorController extends Controller
{
    public function process(Request $request)
    {
        $request->validate([
            'orderId' => 'nullable|integer',
            'paymentMethod' => 'required|string|in:Cash,QRIS',
            'amountPaid' => 'required|numeric',
            'totalPrice' => 'required|numeric',
            'items' => 'required|array', // Required for direct orders (no orderId)
            'items.*.id' => 'required_with:items|integer',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'customerName' => 'nullable|string',
            'orderType' => 'nullable|string|in:Take Away,Takeaway,Counter Order,Table Order',
            'tableId' => 'nullable|integer',
            'note' => 'nullable|string',
            'appliedPromotionId' => 'nullable|integer',
            'appliedPromotionName' => 'nullable|string',
            'appliedPromotionDiscount' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            $orderId = $request->orderId;

            // 1. If it's a direct order (no orderId), create it first
            if (! $orderId) {
                $orderId = DB::table('orders')->insertGetId([
                    'name' => $request->customerName,
                    'tableid' => $request->tableId,
                    'ordertime' => now(),
                    'branchid' => auth()->user()->branchid ?? 1,
                    'ordertype' => $request->orderType ?? 'Counter Order',
                    'status' => 'Cooking', // Start cooking immediately upon payment
                    'totalprice' => $request->totalPrice,
                    'note' => $request->note,
                    'applied_promotion_id' => $request->appliedPromotionId,
                    'applied_promotion_name' => $request->appliedPromotionName,
                    'applied_promotion_discount' => $request->appliedPromotionDiscount ?? 0,
                ]);

                foreach ($request->items as $item) {
                    $isPackage = ! empty($item['is_package']) || (! empty($item['menu']) && ! empty($item['menu']['is_package']));
                    $isProduct = ! empty($item['is_product']);
                    $itemNote = $item['note'] ?? null;

                    if ($isPackage) {
                        // Handle Promotional Package
                        $packageMenus = $item['menus'] ?? ($item['menu']['menus'] ?? []);
                        $packageId = $item['packageid'] ?? ($item['menu']['packageid'] ?? 0);

                        foreach ($packageMenus as $pMenu) {
                            $detailId = DB::table('order_details')->insertGetId([
                                'orderid' => $orderId,
                                'menuid' => (int) $pMenu['id'],
                                'packagename_id' => (int) $packageId,
                                'productid' => 0,
                                'quantity' => $pMenu['qty'] * $item['quantity'],
                                'note' => $item['note'] ?? null,
                            ]);

                            if (! empty($pMenu['addons'])) {
                                foreach ($pMenu['addons'] as $pAddon) {
                                    DB::table('order_addons')->insert([
                                        'detailorderid' => $detailId,
                                        'addonid' => $pAddon['addonid'],
                                    ]);
                                }
                            }
                        }
                    } elseif ($isProduct) {
                        // Handle Direct Product (Barcode scan)
                        DB::table('order_details')->insert([
                            'orderid' => $orderId,
                            'menuid' => null,
                            'packagename_id' => 0,
                            'productid' => (int) $item['id'],
                            'quantity' => $item['quantity'],
                            'note' => $itemNote,
                        ]);
                    } else {
                        // Handle Standard Menu Item
                        $menuId = $item['id'] ?? ($item['menu']['id'] ?? null);

                        $detailId = DB::table('order_details')->insertGetId([
                            'orderid' => $orderId,
                            'menuid' => (int) $menuId,
                            'packagename_id' => 0,
                            'productid' => 0,
                            'quantity' => $item['quantity'],
                            'note' => $itemNote,
                        ]);

                        if (! empty($item['addons'])) {
                            foreach ($item['addons'] as $addon) {
                                DB::table('order_addons')->insert([
                                    'detailorderid' => $detailId,
                                    'addonid' => $addon['addonid'],
                                ]);
                            }
                        }
                    }
                }
            } else {
                // 2. Update existing table order to Paid and update details
                DB::table('orders')->where('orderid', $orderId)->update([
                    'status' => 'Paid',
                    'name' => $request->customerName,
                    'ordertype' => $request->orderType ?? 'Counter Order',
                    'totalprice' => $request->totalPrice,
                    'applied_promotion_id' => $request->appliedPromotionId,
                    'applied_promotion_name' => $request->appliedPromotionName,
                    'applied_promotion_discount' => $request->appliedPromotionDiscount ?? 0,
                ]);
            }

            // Handle Free Item from Buy X Get Y (Unified Logic)
            if ($request->appliedPromotionId) {
                $promo = Promotion::find($request->appliedPromotionId);
                if ($promo && $promo->type === 'BUY_X_GET_Y' && $promo->get_menuid) {
                    // Check if it already exists to avoid duplicates
                    $exists = DB::table('order_details')
                        ->where('orderid', $orderId)
                        ->where('menuid', $promo->get_menuid)
                        ->where('is_free', true)
                        ->exists();

                    if (! $exists) {
                        DB::table('order_details')->insert([
                            'orderid' => $orderId,
                            'menuid' => (int) $promo->get_menuid,
                            'packagename_id' => 0,
                            'productid' => 0,
                            'quantity' => (int) ($promo->get_qty ?? 1),
                            'is_free' => true,
                        ]);
                    }
                }
            } elseif (! empty($request->freeMenuItemId)) {
                // Fallback for frontend-injected freebies if no promo ID linked
                $exists = DB::table('order_details')
                    ->where('orderid', $orderId)
                    ->where('menuid', (int) $request->freeMenuItemId)
                    ->where('is_free', true)
                    ->exists();

                if (! $exists) {
                    DB::table('order_details')->insert([
                        'orderid' => $orderId,
                        'menuid' => (int) $request->freeMenuItemId,
                        'packagename_id' => 0,
                        'productid' => 0,
                        'quantity' => (int) ($request->freeMenuItemQty ?? 1),
                        'is_free' => true,
                    ]);
                }
            }

            // 3. Record or Update Payment
            $paidAmount = $request->amountPaid;
            $totalPrice = $request->totalPrice;
            $changes = max(0, $paidAmount - $totalPrice);

            $paymentData = [
                'orderid' => $orderId,
                'paid' => $paidAmount,
                'changes' => $changes,
                'method' => $request->paymentMethod === 'Cash' ? 'Cashier Payment' : 'QRIS',
                'status' => 'Paid',
                'paymentdate' => now(),
            ];

            // Check if payment record already exists (e.g., from checkout pending)
            $existingPayment = DB::table('payments')->where('orderid', $orderId)->first();
            if ($existingPayment) {
                DB::table('payments')->where('paymentid', $existingPayment->paymentid)->update($paymentData);
                $paymentId = $existingPayment->paymentid;
            } else {
                $paymentId = DB::table('payments')->insertGetId($paymentData);
            }

            DB::table('orders')->where('orderid', $orderId)->update(['paymentid' => $paymentId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'orderId' => $orderId,
                'message' => 'Payment processed successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: '.$e->getMessage(),
            ], 500);
        }
    }

    public function receipt(int $orderId)
    {
        $order = Order::with(['details.menu', 'details.addons.addon', 'details.package', 'details.packageDefinition', 'details.product', 'tableSeat', 'branch'])->find($orderId);
        $payment = DB::table('payments')->where('orderid', $orderId)->first();
        $system = DB::table('system')->first();

        // Use branch info if available, otherwise fallback to system info
        $displayInfo = [
            'name' => $order->branch ? $order->branch->branchname : ($system->systemname ?? 'Joyi'),
            'logo' => $system->systemlogo ?? null,
            'systemname' => $system->systemname ?? 'Joyi',
            'systemlogo' => $system->systemlogo ?? null,
            'address' => $order->branch ? $order->branch->detail_address : ($system->systemaddress ?? ''),
            'contact' => $system->systemcontact ?? '',
        ];

        return Inertia::render('Payments/Receipt', [
            'order' => $order,
            'payment' => $payment,
            'system' => $displayInfo,
        ]);
    }
}
