<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = QueryBuilder::for(Item::class)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where('itemname', 'LIKE', "%{$value}%");
                }),
            ])
            ->defaultSort('-itemid')
            ->paginate(15)
            ->through(fn ($item) => [
                'itemid' => $item->itemid,
                'itemname' => $item->itemname,
                'unit' => $item->unit,
                'impact' => $item->getDeletionImpact(),
            ])
            ->withQueryString();

        return Inertia::render('Master/Items', [
            'items' => $items,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'itemname' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
        ]);

        Item::create([
            'itemname' => $request->itemname,
            'unit' => $request->unit,
        ]);

        return back()->with('success', 'Item created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'itemname' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
        ]);

        $item = Item::findOrFail($id);
        $item->update([
            'itemname' => $request->itemname,
            'unit' => $request->unit,
        ]);

        return back()->with('success', 'Item updated successfully.');
    }

    public function destroy($id)
    {
        $item = Item::findOrFail($id);
        $item->delete();

        return back()->with('success', 'Item deleted successfully.');
    }
}
