<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $filterMonth = $request->input('filter.month') ? date('Y-m-01', strtotime($request->input('filter.month'))) : date('Y-m-01');

        $payrolls = QueryBuilder::for(Payroll::class)
            ->with(['user.branch'])
            ->allowedFilters([
                AllowedFilter::exact('userid'),
                AllowedFilter::callback('branchid', function ($query, $value) {
                    $query->whereHas('user', function ($q) use ($value) {
                        $q->where('branchid', $value);
                    });
                }),
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->whereHas('user', function ($q) use ($value) {
                        $q->where('name', 'LIKE', "%{$value}%");
                    });
                }),
                'month',
            ])
            ->defaultSort('-payrollid')
            ->paginate(15)
            ->through(fn ($p) => array_merge($p->toArray(), [
                'impact' => $p->getDeletionImpact(),
            ]))
            ->withQueryString();

        $users = User::where('roleid', '!=', 1)->get();
        $branches = Branch::all();
        $paidUserIds = Payroll::where('month', $filterMonth)->pluck('userid')->toArray();

        return Inertia::render('Master/Salaries', [
            'payrolls' => $payrolls,
            'users' => $users,
            'branches' => $branches,
            'paidUserIds' => $paidUserIds,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'month' => date('Y-m-01', strtotime($request->month)),
        ]);

        $request->validate([
            'userid' => 'required|exists:users,id',
            'month' => [
                'required',
                'date',
                Rule::unique('payrolls')->where(function ($query) use ($request) {
                    return $query->where('userid', $request->userid)
                        ->where('month', $request->month);
                }),
            ],
            'total_salary' => 'required|integer|min:0',
            'bonus' => 'nullable|integer|min:0',
            'deduction' => 'nullable|integer|min:0',
        ], [
            'month.unique' => 'This employee has already received a payroll entry for this month.',
        ]);

        Payroll::create([
            'userid' => $request->userid,
            'month' => $request->month,
            'total_salary' => $request->total_salary,
            'bonus' => $request->bonus,
            'deduction' => $request->deduction,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Payroll record created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'month' => date('Y-m-01', strtotime($request->month)),
        ]);

        $request->validate([
            'userid' => 'required|exists:users,id',
            'month' => [
                'required',
                'date',
                Rule::unique('payrolls')->where(function ($query) use ($request) {
                    return $query->where('userid', $request->userid)
                        ->where('month', $request->month);
                })->ignore($id, 'payrollid'),
            ],
            'total_salary' => 'required|integer|min:0',
            'bonus' => 'nullable|integer|min:0',
            'deduction' => 'nullable|integer|min:0',
        ], [
            'month.unique' => 'This employee has already received a payroll entry for this month.',
        ]);

        $payroll = Payroll::findOrFail($id);

        $payroll->update([
            'userid' => $request->userid,
            'month' => $request->month,
            'total_salary' => $request->total_salary,
            'bonus' => $request->bonus,
            'deduction' => $request->deduction,
        ]);

        return back()->with('success', 'Payroll record updated successfully.');
    }

    public function destroy($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->delete();

        return back()->with('success', 'Payroll record deleted successfully.');
    }
}
