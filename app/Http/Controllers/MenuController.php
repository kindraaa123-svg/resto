<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $menus = QueryBuilder::for(Menu::class)
            ->with('category')
            ->allowedFilters(['name', 'categoryid'])
            ->defaultSort('menuid')
            ->paginate(20)
            ->withQueryString()
            ->through(function ($menu) {
                return array_merge($menu->toArray(), [
                    'image_url' => $menu->getFirstMediaUrl('menu_images') ?: ($menu->picture ?: null),
                    'category' => $menu->category,
                    'impact' => $menu->getDeletionImpact(),
                ]);
            });

        $categories = Category::all();

        return Inertia::render('Menu/Index', [
            'menus' => $menus,
            'categories' => $categories,
            'filters' => [
                'search' => $request->input('filter.name'),
                'category' => $request->input('filter.categoryid'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'categoryid' => 'required|exists:categories,categoryid',
            'description' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $menu = Menu::create(array_merge(
            $request->only(['name', 'price', 'categoryid']),
            [
                'picture' => '',
                'description' => $request->description ?? '',
            ]
        ));

        if ($request->hasFile('image')) {
            $menu->addMediaFromRequest('image')
                ->toMediaCollection('menu_images', 'menu_uploads');
            $menu->update(['picture' => $menu->getFirstMediaUrl('menu_images')]);
        }

        return Redirect::back()->with('success', 'Menu item created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'categoryid' => 'required|exists:categories,categoryid',
            'description' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        $menu = Menu::findOrFail($id);
        $menu->update($request->only(['name', 'price', 'categoryid', 'description']));

        if ($request->hasFile('image')) {
            $menu->clearMediaCollection('menu_images');
            $menu->addMediaFromRequest('image')
                ->toMediaCollection('menu_images', 'menu_uploads');
            $menu->update(['picture' => $menu->getFirstMediaUrl('menu_images')]);
        }

        return Redirect::back()->with('success', 'Menu item updated successfully.');
    }

    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        return Redirect::back()->with('success', 'Menu item deleted successfully.');
    }
}
