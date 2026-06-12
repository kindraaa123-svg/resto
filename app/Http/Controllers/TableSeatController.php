<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\TableSeat;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

use Spatie\QueryBuilder\AllowedFilter;

class TableSeatController extends Controller
{
    public function index(Request $request)
    {
        $renderer = new ImageRenderer(
            new RendererStyle(250),
            new SvgImageBackEnd
        );
        $writer = new Writer($renderer);

        $tables = QueryBuilder::for(TableSeat::class)
            ->with('branch')
            ->allowedFilters([
                'branchid',
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where('name', 'like', "%{$value}%")
                        ->orWhere('barcode', 'like', "%{$value}%");
                }),
            ])
            ->defaultSort('-tableseatid')
            ->paginate(15)
            ->through(function ($table) use ($writer) {
                return array_merge($table->toArray(), [
                    'qr_code' => $writer->writeString($table->barcode),
                    'impact' => $table->getDeletionImpact(),
                    'branch' => $table->branch,
                ]);
            })
            ->withQueryString();

        $branches = Branch::all();

        return Inertia::render('Master/Tables', [
            'tables' => $tables,
            'branches' => $branches,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'branchid' => 'required|exists:branches,branchid',
        ]);

        TableSeat::create($request->all());

        return back()->with('success', 'Table created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'branchid' => 'required|exists:branches,branchid',
        ]);

        $table = TableSeat::findOrFail($id);
        $table->update($request->all());

        return back()->with('success', 'Table updated successfully.');
    }

    public function destroy($id)
    {
        $table = TableSeat::findOrFail($id);
        $table->delete();

        return back()->with('success', 'Table deleted successfully.');
    }
}
