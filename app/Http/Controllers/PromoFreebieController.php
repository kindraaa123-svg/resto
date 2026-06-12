<?php

namespace App\Http\Controllers;

use App\Models\PromoFreebie;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class PromoFreebieController extends Controller
{
    public function index(Request $request): Response
    {
        $freebies = QueryBuilder::for(PromoFreebie::class)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where('name', 'like', "%{$value}%");
                }),
            ])
            ->defaultSort('-freeid')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Master/Freebies', [
            'freebies' => $freebies,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        PromoFreebie::create($request->all());

        return back()->with('success', 'Freebie created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $freebie = PromoFreebie::findOrFail($id);
        $freebie->update($request->all());

        return back()->with('success', 'Freebie updated successfully.');
    }

    public function destroy($id)
    {
        $freebie = PromoFreebie::findOrFail($id);
        $freebie->delete();

        return back()->with('success', 'Freebie deleted successfully.');
    }
}
