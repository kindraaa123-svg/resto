<?php

namespace App\Http\Controllers;

use App\Helpers\StockChecker;
use App\Models\Addon;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promo;
use App\Models\Promotion;
use App\Models\TableSeat;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $branchId = (int) (auth()->user()->branchid ?? 0);
        if ($branchId === 0 && auth()->user()->hasRole('Superadmin')) {
             // Optional: Pick the first branch or stay at 0 for global view
        }

        $categories = Category::with(['menus' => function($q) {
            $q->with('media')->orderBy('name', 'asc');
        }, 'menus.addons'])->orderBy('categoryname', 'asc')->get()->map(function ($category) use ($branchId) {
            return [
                'id' => $category->categoryid,
                'name' => $category->categoryname,
                'menus' => $category->menus->map(function ($menu) use ($branchId) {
                    $categoryAddons = Addon::where('categoryid', $menu->categoryid)->whereNull('menuid')->get();
                    $menuAddons = $menu->addons;
                    $allAddons = $categoryAddons->concat($menuAddons)->unique('addonid');

                    // Standardize image URL using MediaLibrary logic or fallback
                    $imageUrl = $menu->getFirstMediaUrl('menu_images');
                    if (!$imageUrl && $menu->picture) {
                        $imageUrl = str_starts_with($menu->picture, 'http') 
                            ? $menu->picture 
                            : asset('uploads/' . ltrim($menu->picture, '/'));
                    }

                    return [
                        'id' => $menu->menuid,
                        'name' => $menu->name,
                        'price' => $menu->price,
                        'image_url' => $imageUrl,
                        'is_available' => StockChecker::isMenuAvailable($menu->menuid, $branchId),
                        'max_quantity' => StockChecker::getMaxQuantity($menu->menuid, $branchId),
                        'addons' => $allAddons->map(function ($addon) use ($branchId) {
                            return [
                                'addonid' => $addon->addonid,
                                'name' => $addon->name,
                                'price' => $addon->price,
                                'is_available' => StockChecker::isAddonAvailable($addon->addonid, $branchId),
                            ];
                        }),
                    ];
                }),
            ];
        });

        // Fetch unified promotions (Bundles/Packages)
        $now = now();
        $currentDay = $now->format('l');
        $currentTime = $now->format('H:i:s');

        $activePromos = Promo::with(['package', 'menu', 'addons', 'promoFreebie'])
            ->whereHas('package')
            ->where(function ($q) use ($branchId) {
                $q->where('branchid', $branchId)
                    ->orWhere('branchid', 0)
                    ->orWhereNull('branchid');
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
            ->get();

        $promotions = $activePromos->groupBy(function ($item) {
            return $item->packagename_id.'-'.$item->branchid.'-'.$item->price;
        })->map(function ($group) {
            $first = $group->first();

            // Collect freebies from all items in the group if any
            $freebies = $group->filter(fn($i) => !empty($i->freeid))
                ->map(fn($i) => [
                    'id' => $i->freeid,
                    'name' => $i->promoFreebie->name ?? 'Free Item',
                    'qty' => $i->qty ?? 1,
                ])->values()->toArray();

            return [
                'packageid' => $first->packagename_id,
                'name' => $first->package->packagename ?? 'Promo Package',
                'price' => (float) $first->price,
                'is_package' => true,
                'menus' => $group->map(function ($item) {
                    return [
                        'id' => $item->menuid,
                        'name' => $item->menu->name ?? 'Unknown',
                        'qty' => $item->qty,
                        'price' => $item->price,
                        'addonname' => $item->addons->pluck('name')->join(', '),
                    ];
                })->values()->toArray(),
                'freebies' => $freebies,
                'all_promoids' => $group->pluck('packageid')->toArray(),
                'is_available' => true,
            ];
        })->values()->toArray();

        // Fetch Automatic Discounts (Buy X Get Y, etc.)
        $activePromotions = Promotion::with(['buyMenu', 'getMenu'])
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branchids')
                    ->orWhereJsonLength('branchids', 0)
                    ->orWhereJsonContains('branchids', (string) $branchId)
                    ->orWhereJsonContains('branchids', (int) $branchId)
                    ->orWhereJsonContains('branchids', '0')
                    ->orWhereJsonContains('branchids', 0);
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
            ->get();

        $unpaidOrders = Order::with(['tableSeat', 'details.menu', 'details.product', 'details.packageDefinition', 'details.addons.addon'])
            ->where('status', 'Pending')
            ->orderBy('ordertime', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'orderid' => $order->orderid,
                    'tableid' => $order->tableid,
                    'tablename' => $order->tableSeat->name ?? 'No Table',
                    'customer_name' => $order->name,
                    'orderno' => $order->orderno,
                    'created_at' => $order->ordertime,
                    'ordertype' => $order->ordertype,
                    'total' => (float) $order->totalprice,
                    'details_count' => $order->details->count(),
                    'details' => $order->details->map(function ($detail) {
                        // Calculate unit price from available sources
                        $unitPrice = (float) ($detail->menu?->price 
                            ?? $detail->product?->price 
                            ?? $detail->packageDefinition?->price 
                            ?? 0);
                        
                        $addonPrice = $detail->addons->sum(fn($oa) => $oa->addon?->price ?? 0);
                        $finalUnitPrice = $unitPrice + $addonPrice;

                        return [
                            'orderdetailid' => $detail->detailorderid,
                            'menu' => [
                                'menuid' => $detail->menuid,
                                'id' => $detail->menuid ?? $detail->productid ?? $detail->packagename_id,
                                'name' => $detail->menu?->name ?? $detail->product?->productname ?? $detail->package?->packagename ?? 'Unknown',
                                'price' => $unitPrice,
                                'image_url' => $detail->menu ? $detail->menu->getFirstMediaUrl('menu_images') : null,
                            ],
                            'qty' => $detail->quantity,
                            'total' => (float) ($finalUnitPrice * $detail->quantity),
                            'addons' => $detail->addons->map(function ($oa) {
                                return [
                                    'addonid' => $oa->addonid,
                                    'name' => $oa->addon->name ?? 'Unknown',
                                    'price' => (float) ($oa->addon->price ?? 0),
                                ];
                            }),
                        ];
                    }),
                ];
            });

        $vacantTables = TableSeat::orderBy('name', 'asc')->get();

        $products = Product::orderBy('productname', 'asc')->get()->map(function ($product) use ($branchId) {
            // Standardize image URL for products
            $productImageUrl = null;
            if ($product->picture) {
                $productImageUrl = str_starts_with($product->picture, 'http') 
                    ? $product->picture 
                    : asset('uploads/' . ltrim($product->picture, '/'));
            }

            return [
                'id' => $product->productid,
                'name' => $product->productname,
                'price' => $product->price,
                'image_url' => $productImageUrl,
                'barcode' => $product->barcode,
                'is_product' => true,
                'is_available' => StockChecker::isProductAvailable($product->productid, $branchId),
                'max_quantity' => StockChecker::getMaxProductQuantity($product->productid, $branchId),
            ];
        })->values()->toArray();

        return Inertia::render('Payments/Index', [
            'categories' => $categories,
            'packages' => $promotions,
            'products' => $products,
            'unpaidOrders' => $unpaidOrders,
            'vacantTables' => $vacantTables,
            'activePromotions' => $activePromotions,
        ]);
    }
}
