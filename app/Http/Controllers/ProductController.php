<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = QueryBuilder::for(Product::class)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('productname', 'LIKE', "%{$value}%")
                          ->orWhere('barcode', 'LIKE', "%{$value}%");
                    });
                }),
            ])
            ->defaultSort('-productid')
            ->paginate(15)
            ->through(fn ($p) => [
                'productid' => $p->productid,
                'productname' => $p->productname,
                'barcode' => $p->barcode,
                'price' => $p->price,
                'impact' => $p->getDeletionImpact(),
            ])
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string|max:255|unique:products,barcode',
            'productname' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        Product::create($request->all());

        return back()->with('success', 'Product created successfully.');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'barcode' => 'required|string|max:255|unique:products,barcode,'.$id.',productid',
            'productname' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $product->update($request->all());

        return back()->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }
}
