<?php

namespace App\Http\Controllers;

use App\Models\ProductLog;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class ProductLogController extends Controller
{
    public function index()
    {
        $logs = QueryBuilder::for(ProductLog::class)
            ->with(['product', 'branch'])
            ->allowedFilters(['product.productname', 'type', 'branch.branchname'])
            ->defaultSort('-created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Products/Logs', [
            'logs' => $logs,
        ]);
    }
}
