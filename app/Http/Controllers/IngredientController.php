<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $ingredients = QueryBuilder::for(Ingredient::class)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where('name', 'LIKE', "%{$value}%");
                }),
            ])
            ->defaultSort('-ingredientid')
            ->paginate(15)
            ->through(fn ($i) => [
                'ingredientid' => $i->ingredientid,
                'name' => $i->name,
                'unit' => $i->unit,
                'impact' => $i->getDeletionImpact(),
            ])
            ->withQueryString();

        return Inertia::render('Master/Ingredients', [
            'ingredients' => $ingredients,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name',
            'unit' => 'required|string|in:Kg,Lt,Pcs',
        ]);

        Ingredient::create([
            'name' => $request->name,
            'unit' => $request->unit,
        ]);

        return back()->with('success', 'Ingredient created successfully.');
    }

    public function update(Request $request, $id)
    {
        $ingredient = Ingredient::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name,'.$ingredient->ingredientid.',ingredientid',
            'unit' => 'required|string|in:Kg,Lt,Pcs',
        ]);

        $ingredient->update([
            'name' => $request->name,
            'unit' => $request->unit,
        ]);

        return back()->with('success', 'Ingredient updated successfully.');
    }

    public function destroy($id)
    {
        $ingredient = Ingredient::findOrFail($id);
        $ingredient->delete();

        return back()->with('success', 'Ingredient deleted successfully.');
    }
}
