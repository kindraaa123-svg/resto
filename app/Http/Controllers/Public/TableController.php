<?php

namespace App\Http\Controllers\Public;

use App\Helpers\StockChecker;
use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Promo;
use App\Models\Promotion;
use App\Models\TableSeat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Midtrans\Config;
use Midtrans\Snap;

class TableController extends Controller
{
    private function checkAndHandlePendingOrder($table, $barcode, $ignoreOrderId = null)
    {
        // If we are already viewing or paying for a specific order,
        // don't redirect to another one.
        if ($ignoreOrderId) {
            return null;
        }

        return null;
    }

    private function cancelInternal($orderId)
    {
        DB::beginTransaction();
        try {
            // Get detail IDs for this order to delete related addons first
            $detailIds = DB::table('order_details')->where('orderid', $orderId)->pluck('detailorderid')->toArray();

            if (! empty($detailIds)) {
                DB::table('order_addons')->whereIn('detailorderid', $detailIds)->delete();
            }

            // Delete order details
            DB::table('order_details')->where('orderid', $orderId)->delete();

            // Delete payment record
            DB::table('payments')->where('orderid', $orderId)->delete();

            // Finally delete the order
            Order::where('orderid', $orderId)->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cancel Internal Delete Exception: '.$e->getMessage());
        }
    }

    public function welcome(string $barcode)
    {
        $table = TableSeat::where('barcode', $barcode)->firstOrFail();

        return Inertia::render('Public/Table/Welcome', [
            'table' => $table,
        ]);
    }

    public function menu(string $barcode)
    {
        $table = TableSeat::where('barcode', $barcode)->firstOrFail();

        $pendingOrders = Order::where('tableid', $table->tableseatid)
            ->where('status', 'Pending')
            ->get(['orderid', 'totalprice', 'ordertime'])
            ->map(function ($o) {
                return [
                    'id' => $o->orderid,
                    'total' => $o->totalprice,
                    'expiresAt' => Carbon::parse($o->ordertime)->addMinutes(5)->timestamp,
                ];
            });

        // Fetch Active Promotions
        $now = now();
        $currentDay = $now->format('l');
        $currentTime = $now->format('H:i:s');

        $activePromos = Promo::with(['package', 'menu', 'addons', 'promoFreebie'])
            ->whereHas('package')
            ->where(function ($q) use ($table) {
                $q->where('branchid', $table->branchid)
                    ->orWhereNull('branchid')
                    ->orWhere('branchid', 0);
            })
            ->where(function ($q) use ($now, $currentDay, $currentTime) {
                $q->where('status', 'Available')
                    ->orWhere(function ($sub) use ($now, $currentDay, $currentTime) {
                        $sub->where('status', 'Certain Period')
                            ->where(function ($date) use ($now) {
                                $date->where(function ($d) use ($now) {
                                    $d->whereNull('datefrom')->orWhereDate('datefrom', '<=', $now);
                                })->where(function ($d) use ($now) {
                                    $d->whereNull('dateto')->orWhereDate('dateto', '>=', $now);
                                });
                            })
                            ->where(function ($day) use ($currentDay) {
                                $day->whereNull('days')
                                    ->orWhere('days', '[]')
                                    ->orWhere('days', 'null')
                                    ->orWhereJsonContains('days', $currentDay);
                            })
                            ->where(function ($time) use ($currentTime) {
                                $time->where(function ($t) use ($currentTime) {
                                    $t->whereNull('timefrom')->orWhere('timefrom', '<=', $currentTime);
                                })->where(function ($t) use ($currentTime) {
                                    $t->whereNull('timeto')->orWhere('timeto', '>=', $currentTime);
                                });
                            });
                    });
            })
            ->latest('packageid')
            ->get();

        $groupedPromos = $activePromos->groupBy(function ($item) {
            return $item->packagename_id.'-'.$item->branchid.'-'.$item->price;
        })->map(function ($group) {
            $first = $group->first();

            return [
                'packageid' => $first->packagename_id,
                'name' => $first->package->packagename ?? 'Promo Package',
                'price' => (float) $first->price,
                'menus' => $group->whereNotNull('menuid')->values()->map(fn ($p) => [
                    'id' => $p->menuid,
                    'name' => $p->menu->name ?? 'Unknown',
                    'qty' => $p->qty,
                    'price' => $p->menu->price ?? 0,
                    'addons' => $p->addons->map(fn ($a) => [
                        'addonid' => $a->addonid,
                        'name' => $a->name,
                        'price' => $a->price,
                    ]),
                ]),
                'freebies' => $group->whereNotNull('freeid')->values()->map(fn ($p) => [
                    'id' => $p->freeid,
                    'name' => $p->promoFreebie->name ?? 'Unknown',
                    'qty' => $p->qty,
                ]),
                'all_promoids' => $group->pluck('packageid')->toArray(),
                'is_package' => true,
            ];
        })->values()->toArray();

        // Fetch unified promotions (Discounts, Buy X Get Y)
        $activePromotions = Promotion::with(['buyMenu', 'getMenu'])
            ->where('status', '!=', 'Inactive')
            ->latest('promotionid')
            ->get()
            ->toArray();

        Log::info('PublicTableController Menu: Passing '.count($activePromotions).' promotions for table '.$table->name);

        // Fetch unified promotions for menu price calculation (Collection, not array yet)
        $promoList = Promotion::where('status', '!=', 'Inactive')->get();

        $categories = Category::orderBy('categoryname', 'asc')->get()->map(function ($category) use ($table, $promoList) {
            return [
                'id' => $category->categoryid,
                'name' => $category->categoryname,
                'menus' => Menu::with('addons')->where('categoryid', $category->categoryid)->orderBy('name', 'asc')->get()->map(function ($menu) use ($table, $category, $promoList) {
                    // Calculate best discounted price
                    $originalPrice = (float) $menu->price;
                    $discountedPrice = $originalPrice;
                    $appliedPromo = null;

                    foreach ($promoList as $promo) {
                        // Check if promo applies to this branch
                        $branchMatch = empty($promo->branchids) || in_array((string) $table->branchid, $promo->branchids) || in_array((int) $table->branchid, $promo->branchids);
                        if (! $branchMatch) {
                            continue;
                        }

                        // Check if promo applies to this menu
                        $menuMatch = empty($promo->menuids) || in_array((string) $menu->menuid, $promo->menuids) || in_array((int) $menu->menuid, $promo->menuids);
                        if (! $menuMatch) {
                            continue;
                        }

                        $tempPrice = $originalPrice;
                        $canShowOnMenu = false;

                        if ($promo->type === 'DISCOUNT_FIXED') {
                            $tempPrice = max(0, $originalPrice - $promo->discount_value);
                            $canShowOnMenu = true;
                        } elseif ($promo->type === 'DISCOUNT_PERCENT' && (float) $promo->min_purchase <= 0) {
                            $tempPrice = $originalPrice - ($originalPrice * ($promo->discount_value / 100));
                            $canShowOnMenu = true;
                        }

                        // If it's a better discount and we're allowed to show it on menu
                        if ($canShowOnMenu && $tempPrice < $discountedPrice) {
                            $discountedPrice = $tempPrice;
                            $appliedPromo = ($promo->type === 'DISCOUNT_PERCENT')
                                ? "Disc {$promo->discount_value}%"
                                : $promo->name;
                        }
                    }

                    // Fetch category-wide addons (handle various 'empty' states for menuid)
                    $categoryAddons = Addon::where('categoryid', $category->categoryid)
                        ->where(function ($q) {
                            $q->whereNull('menuid')
                                ->orWhere('menuid', 0)
                                ->orWhere('menuid', '');
                        })
                        ->get();

                    // Combine with menu-specific addons
                    $allAddons = $menu->addons->concat($categoryAddons)->unique('addonid');

                    return [
                        'id' => $menu->menuid,
                        'name' => $menu->name,
                        'description' => $menu->description,
                        'price' => $originalPrice,
                        'discounted_price' => $discountedPrice,
                        'applied_promo' => $appliedPromo,
                        'image' => $menu->getFirstMediaUrl('menu_images') ?: ($menu->picture ? asset('storage/'.$menu->picture) : null),
                        'is_available' => StockChecker::isMenuAvailable($menu->menuid, $table->branchid),
                        'addons' => $allAddons->map(fn ($a) => [
                            'addonid' => $a->addonid,
                            'name' => $a->name,
                            'price' => $a->price,
                            'is_available' => StockChecker::isAddonAvailable($a->addonid, $table->branchid),
                        ]),
                    ];
                }),
            ];
        })->filter(fn ($cat) => count($cat['menus']) > 0)->values();

        return Inertia::render('Public/Table/Menu', [
            'table' => $table,
            'categories' => $categories,
            'packages' => $groupedPromos,
            'promotions' => $activePromotions,
            'pendingOrders' => $pendingOrders,
        ]);
    }

    public function processCheckout(Request $request, string $barcode)
    {
        $table = TableSeat::where('barcode', $barcode)->firstOrFail();

        $request->validate([
            'items' => 'required|array|min:1',
            'paymentMethod' => 'required|string',
            'totalPrice' => 'required|numeric', // This is the total from frontend (should already include tax)
        ]);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'tableid' => $table->tableseatid,
                'ordertime' => now(),
                'branchid' => $table->branchid,
                'ordertype' => 'Table Order',
                'status' => 'Pending',
                'totalprice' => $request->totalPrice,
                'note' => $request->note,
                'applied_promotion_id' => $request->appliedPromotionId,
                'applied_promotion_name' => $request->appliedPromotionName,
                'applied_promotion_discount' => $request->appliedPromotionDiscount ?? 0,
            ]);

            foreach ($request->items as $item) {
                $isPackage = ! empty($item['is_package']) || (! empty($item['menu']) && ! empty($item['menu']['is_package']));

                if ($isPackage) {
                    // Handle Promotional Package
                    $packageMenus = $item['menus'] ?? ($item['menu']['menus'] ?? []);
                    $packageId = $item['packageid'] ?? ($item['menu']['packageid'] ?? 0);

                    $itemNote = $item['note'] ?? ($item['menu']['note'] ?? null);

                    foreach ($packageMenus as $pMenu) {
                        $detailId = DB::table('order_details')->insertGetId([
                            'orderid' => $order->orderid,
                            'menuid' => (int) $pMenu['id'],
                            'packagename_id' => (int) $packageId,
                            'productid' => 0,
                            'quantity' => $pMenu['qty'] * $item['quantity'],
                            'note' => $itemNote,
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
                } else {
                    // Handle Standard Menu Item
                    $menuId = $item['id'] ?? ($item['menu']['id'] ?? null);

                    $detail = DB::table('order_details')->insertGetId([
                        'orderid' => $order->orderid,
                        'menuid' => (int) $menuId,
                        'packagename_id' => 0,
                        'productid' => 0,
                        'quantity' => $item['quantity'],
                        'note' => $item['note'] ?? null,
                    ]);

                    if (! empty($item['addons'])) {
                        foreach ($item['addons'] as $addon) {
                            DB::table('order_addons')->insert([
                                'detailorderid' => $detail,
                                'addonid' => $addon['addonid'],
                            ]);
                        }
                    }
                }
            }

            if ($request->paymentMethod === 'QRIS') {
                Config::$serverKey = config('services.midtrans.server_key');
                Config::$isProduction = config('services.midtrans.is_production');
                Config::$isSanitized = true;
                Config::$is3ds = true;

                $params = [
                    'transaction_details' => [
                        'order_id' => 'ORD-'.$order->orderid.'-'.Str::random(5),
                        'gross_amount' => (int) $request->totalPrice,
                    ],
                    'customer_details' => [
                        'first_name' => 'Table '.$table->name,
                    ],
                ];

                $snapToken = Snap::getSnapToken($params);
                $order->update(['snap_token' => $snapToken]);
            } else {
                DB::table('payments')->insert([
                    'orderid' => $order->orderid,
                    'method' => 'Cashier Payment',
                    'status' => 'Pending',
                    'paid' => 0,
                    'changes' => 0,
                    'paymentdate' => now(),
                ]);
            }

            // Handle Free Item from Buy X Get Y (Backend-driven for extra safety)
            if ($request->appliedPromotionId) {
                $promo = Promotion::find($request->appliedPromotionId);
                if ($promo && $promo->type === 'BUY_X_GET_Y' && $promo->get_menuid) {
                    // Check if it already exists to avoid duplicates
                    $exists = DB::table('order_details')
                        ->where('orderid', $order->orderid)
                        ->where('menuid', $promo->get_menuid)
                        ->where('is_free', true)
                        ->exists();

                    if (! $exists) {
                        DB::table('order_details')->insert([
                            'orderid' => $order->orderid,
                            'menuid' => (int) $promo->get_menuid,
                            'packagename_id' => 0,
                            'productid' => 0,
                            'quantity' => (int) ($promo->get_qty ?? 1),
                            'is_free' => true,
                        ]);
                    }
                }
            }

            DB::commit();

            if ($request->paymentMethod === 'QRIS') {
                return redirect()->route('public.table.payment', ['barcode' => $barcode, 'order' => $order->orderid]);
            }

            return back()->with('success', 'Order placed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error processing order: '.$e->getMessage());
        }
    }

    public function payment(string $barcode, int $order)
    {
        $table = TableSeat::where('barcode', $barcode)->firstOrFail();

        $orderData = Order::with(['details.menu', 'details.addons.addon'])
            ->where('orderid', $order)
            ->first();

        if (! $orderData) {
            return redirect()->route('public.table.menu', ['barcode' => $barcode])->with('error', 'Order session expired.');
        }

        if ($orderData->status !== 'Pending') {
            return redirect()->route('public.table.waiting', ['barcode' => $barcode, 'order' => $order]);
        }

        $ordertime = Carbon::parse($orderData->ordertime);
        $expiresAt = $ordertime->addMinutes(5)->timestamp;

        if (now()->timestamp > $expiresAt) {
            $this->cancelInternal($order);

            return redirect()->route('public.table.menu', ['barcode' => $barcode])->with('error', 'Order session expired.');
        }

        $subtotal = 0;
        $items = $orderData->details->map(function ($detail) use (&$subtotal) {
            $itemTotal = ($detail->menu->price + $detail->addons->sum(fn ($oa) => $oa->addon->price)) * $detail->quantity;
            $subtotal += $itemTotal;

            return [
                'id' => $detail->menu->menuid,
                'name' => $detail->menu->name,
                'price' => $detail->menu->price,
                'quantity' => $detail->quantity,
                'image' => $detail->menu->getFirstMediaUrl('menu_images') ?: ($detail->menu->picture ? asset('storage/'.$detail->menu->picture) : null),
                'addons' => $detail->addons->map(function ($oa) {
                    return [
                        'name' => $oa->addon->name,
                        'price' => $oa->addon->price,
                    ];
                }),
                'itemTotalPrice' => $itemTotal,
            ];
        });

        $tax = $subtotal * 0.11;

        return Inertia::render('Public/Table/Payment', [
            'table' => $table,
            'orderId' => (string) $order,
            'items' => $items,
            'expiresAt' => $expiresAt,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'totalPrice' => $orderData->totalprice,
            'snapToken' => $orderData->snap_token,
            'midtransClientKey' => config('services.midtrans.client_key'),
            'midtransIsProduction' => config('services.midtrans.is_production'),
        ]);
    }

    public function waiting(string $barcode, int $order)
    {
        $table = TableSeat::where('barcode', $barcode)->firstOrFail();
        $orderData = Order::with(['details.menu', 'details.addons.addon'])->where('orderid', $order)->first();

        if (! $orderData) {
            return redirect()->route('public.table.menu', ['barcode' => $barcode])->with('error', 'Order session expired.');
        }

        if ($orderData->status !== 'Pending' && ! in_array(strtolower($orderData->status), ['cooking', 'served'])) {
            return redirect()->route('public.table.menu', ['barcode' => $barcode])->with('error', 'Order already processed or cancelled.');
        }

        $ordertime = Carbon::parse($orderData->ordertime);
        $expiresAt = $ordertime->addMinutes(5)->timestamp;

        if ($orderData->status === 'Pending' && now()->timestamp > $expiresAt) {
            $this->cancelInternal($order);

            return redirect()->route('public.table.menu', ['barcode' => $barcode])->with('error', 'Order session expired.');
        }

        $subtotal = 0;
        foreach ($orderData->details as $detail) {
            $subtotal += ($detail->menu->price + $detail->addons->sum(fn ($oa) => $oa->addon->price)) * $detail->quantity;
        }
        $tax = $subtotal * 0.11;

        return Inertia::render('Public/Table/Waiting', [
            'table' => $table,
            'orderId' => (string) $order,
            'expiresAt' => $expiresAt,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'totalPrice' => $orderData->totalprice,
            'paymentMethod' => DB::table('payments')->where('orderid', $order)->value('method'),
            'status' => strtolower($orderData->status),
        ]);
    }

    public function checkStatus(string $barcode, int $order)
    {
        $orderData = Order::where('orderid', $order)->first();
        if (! $orderData) {
            return response()->json(['status' => 'expired']);
        }

        return response()->json(['status' => strtolower($orderData->status)]);
    }

    public function cancelOrder(string $barcode, int $order)
    {
        $orderData = Order::where('orderid', $order)->where('status', 'Pending')->first();
        if ($orderData) {
            DB::transaction(function () use ($orderData, $order) {
                $orderData->update(['status' => 'Cancelled']);
                DB::table('payments')->where('orderid', $order)->update(['status' => 'Cancelled']);
            });
        }

        return redirect()->route('public.table.menu', ['barcode' => $barcode]);
    }
}
