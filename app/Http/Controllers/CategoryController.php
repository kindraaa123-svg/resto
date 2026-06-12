<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

use Spatie\QueryBuilder\AllowedFilter;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = QueryBuilder::for(Category::class)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where('categoryname', 'like', "%{$value}%");
                }),
            ])
            ->defaultSort('-categoryid')
            ->paginate(15)
            ->through(fn ($cat) => [
                'categoryid' => $cat->categoryid,
                'categoryname' => $cat->categoryname,
                'impact' => $cat->getDeletionImpact(),
            ])
            ->withQueryString();

        return Inertia::render('Master/Categories', [
            'categories' => $categories,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoryname' => 'required|string|max:255',
        ]);

        Category::create($request->all());

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'categoryname' => 'required|string|max:255',
        ]);

        $category = Category::findOrFail($id);
        $category->update($request->all());

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
