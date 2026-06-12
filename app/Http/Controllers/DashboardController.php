<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userBranchId = auth()->user()->branchid;

        $incomeQuery = DB::table('payments')
            ->join('orders', 'payments.orderid', '=', 'orders.orderid')
            ->where('payments.status', 'Paid');

        $dailyIncomeQuery = DB::table('payments')
            ->join('orders', 'payments.orderid', '=', 'orders.orderid')
            ->select(
                DB::raw('DATE(payments.paymentdate) as date'),
                DB::raw('SUM(payments.paid) as total')
            )
            ->where('payments.status', 'Paid')
            ->where('payments.paymentdate', '>=', now()->subDays(6));

        $orderCountQuery = DB::table('orders')->whereIn('status', ['Cooking', 'Served']);
        $todayOrderCountQuery = DB::table('orders')->whereDate('ordertime', today())->whereIn('status', ['Cooking', 'Served']);

        if ($userBranchId) {
            $incomeQuery->where('orders.branchid', $userBranchId);
            $dailyIncomeQuery->where('orders.branchid', $userBranchId);
            $orderCountQuery->where('branchid', $userBranchId);
            $todayOrderCountQuery->where('branchid', $userBranchId);
        }

        $income = $incomeQuery->sum('payments.paid');

        $dailyIncome = $dailyIncomeQuery->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Fill missing days with 0
        $chartData = [];
        $labels = [];
        $series = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $label = now()->subDays($i)->format('D, d M');
            $labels[] = $label;

            $dayData = $dailyIncome->firstWhere('date', $date);
            $series[] = $dayData ? (float) $dayData->total : 0;
        }

        // Additional stats
        $orderCount = $orderCountQuery->count();
        $todayOrderCount = $todayOrderCountQuery->count();

        return Inertia::render('Home', [
            'income' => (float) $income,
            'chartData' => [
                'labels' => $labels,
                'series' => $series,
            ],
            'stats' => [
                'totalOrders' => $orderCount,
                'todayOrders' => $todayOrderCount,
            ],
        ]);
    }
}
