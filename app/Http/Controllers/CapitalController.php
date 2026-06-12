<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\Capital;
use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

use Spatie\QueryBuilder\AllowedFilter;

class CapitalController extends Controller
{
    public function index(Request $request)
    {
        $capitals = QueryBuilder::for(Capital::class)
            ->with(['menu', 'addon'])
            ->allowedFilters([
                'menuid',
                'addonid',
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->whereHas('menu', function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%");
                    })->orWhereHas('addon', function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%");
                    });
                }),
            ])
            ->defaultSort('-modalid')
            ->paginate(15)
            ->withQueryString();

        $menus = Menu::all();
        $addons = Addon::all();

        return Inertia::render('Master/Capitals', [
            'capitals' => $capitals,
            'menus' => $menus,
            'addons' => $addons,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'menuid' => 'required_without:addonid|nullable|exists:menus,menuid',
            'addonid' => 'required_without:menuid|nullable|exists:addons,addonid',
            'price' => 'required|string|max:255',
        ]);

        Capital::create($request->all());

        return back()->with('success', 'Capital item created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'menuid' => 'required_without:addonid|nullable|exists:menus,menuid',
            'addonid' => 'required_without:menuid|nullable|exists:addons,addonid',
            'price' => 'required|string|max:255',
        ]);

        $capital = Capital::findOrFail($id);
        $capital->update($request->all());

        return back()->with('success', 'Capital item updated successfully.');
    }

    public function destroy($id)
    {
        $capital = Capital::findOrFail($id);
        $capital->delete();

        return back()->with('success', 'Capital item deleted successfully.');
    }
}
