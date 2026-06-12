<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Menu;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class PromotionController extends Controller
{
    public function index()
    {
        $promotions = QueryBuilder::for(Promotion::class)
            ->with(['buyMenu', 'getMenu'])
            ->allowedFilters(['name', 'type', 'status'])
            ->defaultSort('-promotionid')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Promotions/Index', [
            'promotions' => $promotions,
            'branches' => Branch::all(['branchid', 'branchname']),
            'menus' => Menu::with('addons')->get(['menuid', 'name', 'price']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:DISCOUNT_PERCENT,DISCOUNT_FIXED,BUY_X_GET_Y',
            'discount_value' => 'nullable|numeric|min:0',
            'buy_qty' => 'nullable|integer|min:1',
            'get_qty' => 'nullable|integer|min:1',
            'buy_menuid' => 'nullable|exists:menus,menuid',
            'get_menuid' => 'nullable|exists:menus,menuid',
            'min_purchase' => 'nullable|numeric|min:0',
            'branchids' => 'nullable|array',
            'branchids.*' => 'exists:branches,branchid',
            'menuids' => 'nullable|array',
            'menuids.*' => 'exists:menus,menuid',
            'menu_addons' => 'nullable|array',
            'status' => 'required|in:Available,Certain Period,Inactive',
            'datefrom' => 'nullable|date',
            'dateto' => 'nullable|date|after_or_equal:datefrom',
            'timefrom' => 'nullable',
            'timeto' => 'nullable',
            'days' => 'nullable|array',
        ]);

        Promotion::create($request->all());

        return back()->with('success', 'Promotion created successfully.');
    }

    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:DISCOUNT_PERCENT,DISCOUNT_FIXED,BUY_X_GET_Y',
            'discount_value' => 'nullable|numeric|min:0',
            'buy_qty' => 'nullable|integer|min:1',
            'get_qty' => 'nullable|integer|min:1',
            'buy_menuid' => 'nullable|exists:menus,menuid',
            'get_menuid' => 'nullable|exists:menus,menuid',
            'min_purchase' => 'nullable|numeric|min:0',
            'branchids' => 'nullable|array',
            'branchids.*' => 'exists:branches,branchid',
            'menuids' => 'nullable|array',
            'menuids.*' => 'exists:menus,menuid',
            'menu_addons' => 'nullable|array',
            'status' => 'required|in:Available,Certain Period,Inactive',
            'datefrom' => 'nullable|date',
            'dateto' => 'nullable|date|after_or_equal:datefrom',
            'timefrom' => 'nullable',
            'timeto' => 'nullable',
            'days' => 'nullable|array',
        ]);

        $promotion->update($request->all());

        return back()->with('success', 'Promotion updated successfully.');
    }

    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return back()->with('success', 'Promotion deleted successfully.');
    }
}
