<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Item;
use App\Models\Menu;
use App\Models\Operational;
use App\Models\Payroll;
use App\Models\Promo;
use App\Models\Promotion;
use App\Models\TableSeat;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecycleBinController extends Controller
{
    private $models = [
        'user' => User::class,
        'category' => Category::class,
        'menu' => Menu::class,
        'addon' => Addon::class,
        'package' => Promo::class,
        'promotion' => Promotion::class,
        'item' => Item::class,
        'branch' => Branch::class,
        'table' => TableSeat::class,
        'ingredient' => Ingredient::class,
        'operational' => Operational::class,
        'payroll' => Payroll::class,
    ];

    public function index(Request $request)
    {
        $type = $request->input('type', 'user');

        if (! array_key_exists($type, $this->models)) {
            $type = 'user';
        }

        $modelClass = $this->models[$type];
        $items = $modelClass::onlyTrashed()->with('deletedByUser')->paginate(10)->withQueryString();

        // Format items for display
        $formattedItems = $items->through(function ($item) use ($type) {
            $data = [
                'id' => $this->getId($item, $type),
                'deleted_at' => $item->deleted_at,
                'deleted_by' => $item->deletedByUser->name ?? 'System/Unknown',
            ];

            switch ($type) {
                case 'user':
                    $data['name'] = $item->name;
                    $data['info'] = $item->email;
                    break;
                case 'category':
                    $data['name'] = $item->categoryname;
                    $data['info'] = 'Category ID: '.$item->categoryid;
                    break;
                case 'menu':
                    $data['name'] = $item->name;
                    $data['info'] = 'Price: '.$item->price;
                    break;
                case 'addon':
                    $data['name'] = $item->name;
                    $data['info'] = 'Price: '.$item->price;
                    break;
                case 'package':
                    $data['name'] = 'Link: '.($item->package->packagename ?? 'Unknown').' - '.($item->menu->name ?? 'Unknown');
                    $data['info'] = 'Promo ID: '.$item->packageid;
                    break;
                case 'promotion':
                    $data['name'] = $item->name;
                    $data['info'] = 'Type: '.$item->type;
                    break;
                case 'item':
                    $data['name'] = $item->itemname;
                    $data['info'] = 'Item ID: '.$item->itemid;
                    break;
                case 'branch':
                    $data['name'] = $item->branchname;
                    $data['info'] = $item->detail_address;
                    break;
                case 'table':
                    $data['name'] = $item->name;
                    $data['info'] = 'Barcode: '.$item->barcode;
                    break;
                case 'ingredient':
                    $data['name'] = $item->name;
                    $data['info'] = 'Ingredient ID: '.$item->ingredientid;
                    break;
                case 'operational':
                    $data['name'] = $item->name;
                    $data['info'] = 'Amount: '.$item->amount;
                    break;
                case 'payroll':
                    $data['name'] = 'Payroll for '.($item->user->name ?? 'Unknown');
                    $data['info'] = 'Month: '.$item->month;
                    break;
            }

            return $data;
        });

        return Inertia::render('System/RecycleBin', [
            'items' => $formattedItems,
            'currentType' => $type,
            'types' => array_keys($this->models),
        ]);
    }

    private function getId($item, $type)
    {
        switch ($type) {
            case 'user': return $item->id;
            case 'category': return $item->categoryid;
            case 'menu': return $item->menuid;
            case 'addon': return $item->addonid;
            case 'package': return $item->packageid;
            case 'promotion': return $item->promotionid;
            case 'item': return $item->itemid;
            case 'branch': return $item->branchid;
            case 'table': return $item->tableseatid;
            case 'ingredient': return $item->ingredientid;
            case 'operational': return $item->operationalid;
            case 'payroll': return $item->payrollid;
            default: return $item->id;
        }
    }

    public function restore(Request $request, $type, $id)
    {
        if (! array_key_exists($type, $this->models)) {
            return back()->with('error', 'Invalid type.');
        }

        $modelClass = $this->models[$type];
        $item = $modelClass::onlyTrashed()->find($id);

        if ($item) {
            $item->restore();

            return back()->with('success', ucfirst($type).' restored successfully.');
        }

        return back()->with('error', 'Item not found.');
    }

    public function forceDelete(Request $request, $type, $id)
    {
        if (! array_key_exists($type, $this->models)) {
            return back()->with('error', 'Invalid type.');
        }

        $modelClass = $this->models[$type];
        $item = $modelClass::onlyTrashed()->find($id);

        if ($item) {
            $item->forceDelete();

            return back()->with('success', ucfirst($type).' deleted permanently.');
        }

        return back()->with('error', 'Item not found.');
    }
}
