<?php

namespace App\Http\Controllers;

use App\Exports\FinancialReportExport;
use App\Models\Operational;
use App\Models\Order;
use Carbon\Carbon;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'daily');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');
        $month = $request->input('month');
        $year = $request->input('year', date('Y'));

        // Default logic for Daily: Show current month if no filter
        if ($period === 'daily' && ! $customStart && ! $customEnd) {
            if ($month) {
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
                $endDate = $startDate->copy()->endOfMonth()->endOfDay();
            } else {
                $startDate = Carbon::now()->startOfMonth()->startOfDay();
                $endDate = Carbon::now()->endOfMonth()->endOfDay();
            }
            $customStart = $startDate->format('Y-m-d');
            $customEnd = $endDate->format('Y-m-d');
        } elseif ($period === 'weekly' && ! $customStart && ! $customEnd) {
            // For Weekly: Show last 8 weeks by default
            $endDate = Carbon::now()->endOfWeek();
            $startDate = $endDate->copy()->subWeeks(8)->startOfWeek();
            $customStart = $startDate->format('Y-m-d');
            $customEnd = $endDate->format('Y-m-d');
        } elseif ($period === 'monthly' && ! $customStart && ! $customEnd) {
            // For Monthly: Default to the selected year (or current year)
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear()->startOfDay();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear()->endOfDay();
            $customStart = $startDate->format('Y-m-d');
            $customEnd = $endDate->format('Y-m-d');
        }

        $reportData = $this->generateReportData($period, $customStart, $customEnd);

        // For summary cards stats
        if ($customStart && $customEnd) {
            $startDate = Carbon::parse($customStart)->startOfDay();
            $endDate = Carbon::parse($customEnd)->endOfDay();
        } else {
            $firstPayment = DB::table('payments')->where('status', 'Paid')->min('paymentdate');
            $startDate = $firstPayment ? Carbon::parse($firstPayment)->startOfDay() : Carbon::parse('2026-05-29');
            $endDate = Carbon::now()->endOfDay();
        }

        $overallStats = $this->getFinancialDataForRange($startDate, $endDate);

        return Inertia::render('Reports/Index', [
            'reportData' => $reportData,
            'currentPeriod' => $period,
            'stats' => $overallStats,
            'startDate' => $startDate->translatedFormat('d M Y'),
            'endDate' => $endDate->format('Y-m-d'),
            'currentMonth' => $month ?? Carbon::parse($customStart ?? now())->format('m'),
            'currentYear' => $year,
            'locale' => app()->getLocale(),
        ]);
    }

    private function generateReportData($period, $customStart = null, $customEnd = null)
    {
        if ($customStart && $customEnd) {
            $startDate = Carbon::parse($customStart)->startOfDay();
            $endDate = Carbon::parse($customEnd)->endOfDay();
        } else {
            $firstPayment = DB::table('payments')->where('status', 'Paid')->min('paymentdate');
            $startDate = $firstPayment ? Carbon::parse($firstPayment)->startOfDay() : Carbon::parse('2026-05-29');
            $endDate = Carbon::now()->endOfDay();
        }

        $reportData = [];

        if ($period === 'daily') {
            $curr = clone $endDate;
            while ($curr >= $startDate) {
                $start = $curr->copy()->startOfDay();
                $end = $curr->copy()->endOfDay();
                $data = $this->getFinancialDataForRange($start, $end);
                if ($data['income'] > 0 || $data['total_outcome'] > 0) {
                    $reportData[] = array_merge([
                        'date_label' => $curr->translatedFormat('d M Y'),
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $end->format('Y-m-d'),
                    ], $data);
                }
                $curr->subDay();
            }
        } elseif ($period === 'weekly') {
            $curr = clone $endDate;
            $startLimit = $startDate->copy()->startOfWeek();
            while ($curr >= $startLimit) {
                $start = $curr->copy()->startOfWeek();
                $end = $curr->copy()->endOfWeek();
                $data = $this->getFinancialDataForRange($start, $end);
                if ($data['income'] > 0 || $data['total_outcome'] > 0) {
                    $reportData[] = array_merge([
                        'date_label' => $start->translatedFormat('d M').' - '.$end->translatedFormat('d M Y'),
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $end->format('Y-m-d'),
                    ], $data);
                }
                $curr->subWeek();
            }
        } elseif ($period === 'monthly') {
            $curr = clone $endDate;
            $startMonth = $startDate->copy()->startOfMonth();
            while ($curr >= $startMonth) {
                $start = $curr->copy()->startOfMonth();
                $end = $curr->copy()->endOfMonth();
                $data = $this->getFinancialDataForRange($start, $end);
                if ($data['income'] > 0 || $data['total_outcome'] > 0) {
                    $reportData[] = array_merge([
                        'date_label' => $curr->translatedFormat('F Y'),
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $end->format('Y-m-d'),
                    ], $data);
                }
                $curr->subMonth();
            }
        } elseif ($period === 'yearly') {
            $curr = clone $endDate;
            $startYear = $startDate->copy()->startOfYear();
            while ($curr >= $startYear) {
                $start = $curr->copy()->startOfYear();
                $end = $curr->copy()->endOfYear();
                $data = $this->getFinancialDataForRange($start, $end);
                if ($data['income'] > 0 || $data['total_outcome'] > 0) {
                    $reportData[] = array_merge([
                        'date_label' => $curr->translatedFormat('Y'),
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $end->format('Y-m-d'),
                    ], $data);
                }
                $curr->subYear();
            }
        }

        return $reportData;
    }

    private function getFinancialDataForRange($start, $end)
    {
        // Income from completed/successful orders (exclude Cancelled)
        $income = (float) Order::whereBetween('ordertime', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->sum('totalprice');

        // Operational expenses
        $operational = (float) Operational::whereBetween('created_at', [$start, $end])->sum('amount');

        // Payroll
        $payroll = (float) DB::table('payrolls')
            ->whereBetween('month', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum(DB::raw('total_salary + bonus - deduction'));

        // COGS (HPP) - Optimized calculation
        $cogs = 0;

        $totalOutcome = $operational + $payroll + $cogs;
        $profit = $income - $totalOutcome;

        return [
            'income' => $income,
            'operational' => $operational,
            'payroll' => $payroll,
            'stock' => $cogs,
            'total_outcome' => $totalOutcome,
            'profit' => $profit,
        ];
    }

    private function getDetailedSales($start, $end = null)
    {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : $startDate->copy()->endOfDay();

        $orders = Order::whereBetween('ordertime', [$startDate, $endDate])
            ->where('status', '!=', 'Cancelled')
            ->with(['details.menu', 'payment'])
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $items = [];
        foreach ($orders as $order) {
            $paymentMethod = $order->payment?->method ?? 'Cash';
            $orderTotal = (float) $order->totalprice;

            $orderSubtotal = 0;
            foreach ($order->details as $detail) {
                $orderSubtotal += ((float) ($detail->menu->price ?? 0) * (int) $detail->quantity);
            }

            foreach ($order->details as $detail) {
                $itemSubtotal = (float) ($detail->menu->price ?? 0) * (int) $detail->quantity;
                $itemPriceWithTax = $orderSubtotal > 0 ? ($itemSubtotal / $orderSubtotal) * $orderTotal : 0;

                $items[] = [
                    'time' => $order->ordertime ? ($end ? $order->ordertime->format('d/m H:i') : $order->ordertime->format('H:i')) : '-',
                    'item' => $detail->menu->name ?? 'Unknown',
                    'qty' => $detail->quantity,
                    'price' => $itemPriceWithTax,
                    'payment' => $paymentMethod,
                    'hpp' => 0, // COGS logic removed as per previous context
                    'profit' => $itemPriceWithTax,
                ];
            }
        }

        return $items;
    }

    private function getMonthlyPandLData($month, $year)
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Breakdown Operating Revenue
        $takeAwayRevenue = (float) Order::whereBetween('ordertime', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->where('ordertype', 'Take Away')
            ->sum('totalprice');

        $tableOrderRevenue = (float) Order::whereBetween('ordertime', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->where('ordertype', 'Table Order')
            ->sum('totalprice');

        $cashierRevenue = (float) Order::whereBetween('ordertime', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->where('ordertype', 'Counter Order')
            ->sum('totalprice');

        $totalOperatingRevenue = $takeAwayRevenue + $tableOrderRevenue + $cashierRevenue;

        // Salaries
        $salariesExpense = (float) DB::table('payrolls')
            ->whereBetween('month', [$start, $end])
            ->sum(DB::raw('total_salary + bonus - deduction'));

        // Utilities
        $utilitiesExpense = (float) Operational::whereBetween('created_at', [$start, $end])
            ->whereIn('category', ['Electricity', 'Water'])
            ->sum('amount');

        // Others
        $othersExpense = (float) Operational::whereBetween('created_at', [$start, $end])
            ->where('category', 'Others')
            ->sum('amount');

        // COGS (HPP)
        $financialData = $this->getFinancialDataForRange($start, $end);
        $cogs = $financialData['stock'];

        return [
            'revenue' => [
                ['code' => '4-101', 'label' => 'Revenue - Take Away', 'amount' => $takeAwayRevenue],
                ['code' => '4-102', 'label' => 'Revenue - Table Order', 'amount' => $tableOrderRevenue],
                ['code' => '4-103', 'label' => 'Revenue - Cashier (Counter)', 'amount' => $cashierRevenue],
                ['code' => '4-300', 'label' => 'Other Revenue', 'amount' => 0],
            ],
            'expenses' => [
                ['code' => '5-100', 'label' => 'Cost of Goods Sold (COGS)', 'amount' => $cogs],
                ['code' => '6-200', 'label' => 'Salaries Expense', 'amount' => $salariesExpense],
                ['code' => '6-300', 'label' => 'Utilities (Elec, Water)', 'amount' => $utilitiesExpense],
                ['code' => '6-1000', 'label' => 'Miscellaneous Expense', 'amount' => $othersExpense],
            ],
            'month_label' => $start->format('F Y'),
        ];
    }

    private function getYearlyPandLData($year)
    {
        $start = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $end = $start->copy()->endOfYear();

        // Breakdown Operating Revenue
        $takeAwayRevenue = (float) Order::whereBetween('ordertime', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->where('ordertype', 'Take Away')
            ->sum('totalprice');

        $tableOrderRevenue = (float) Order::whereBetween('ordertime', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->where('ordertype', 'Table Order')
            ->sum('totalprice');

        $cashierRevenue = (float) Order::whereBetween('ordertime', [$start, $end])
            ->where('status', '!=', 'Cancelled')
            ->where('ordertype', 'Counter Order')
            ->sum('totalprice');

        // Salaries
        $salariesExpense = (float) DB::table('payrolls')
            ->whereBetween('month', [$start, $end])
            ->sum(DB::raw('total_salary + bonus - deduction'));

        // Utilities
        $utilitiesExpense = (float) Operational::whereBetween('created_at', [$start, $end])
            ->whereIn('category', ['Electricity', 'Water'])
            ->sum('amount');

        // Others
        $othersExpense = (float) Operational::whereBetween('created_at', [$start, $end])
            ->where('category', 'Others')
            ->sum('amount');

        // COGS (HPP) - Re-use optimized logic for the whole year
        $financialData = $this->getFinancialDataForRange($start, $end);
        $cogs = $financialData['stock'];

        return [
            'revenue' => [
                ['code' => '4-101', 'label' => 'Revenue - Take Away', 'amount' => $takeAwayRevenue],
                ['code' => '4-102', 'label' => 'Revenue - Table Order', 'amount' => $tableOrderRevenue],
                ['code' => '4-103', 'label' => 'Revenue - Cashier (Counter)', 'amount' => $cashierRevenue],
                ['code' => '4-300', 'label' => 'Other Revenue', 'amount' => 0],
            ],
            'expenses' => [
                ['code' => '5-100', 'label' => 'Cost of Goods Sold (COGS)', 'amount' => $cogs],
                ['code' => '6-200', 'label' => 'Salaries Expense', 'amount' => $salariesExpense],
                ['code' => '6-300', 'label' => 'Utilities (Elec, Water)', 'amount' => $utilitiesExpense],
                ['code' => '6-1000', 'label' => 'Miscellaneous Expense', 'amount' => $othersExpense],
            ],
            'month_label' => $start->format('Y'), // Showing only the year for yearly report
        ];
    }

    public function financialReports(Request $request)
    {
        return $this->index($request);
    }

    public function exportFinancialExcel(Request $request)
    {
        $period = $request->input('period', 'daily');
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        if (($period === 'daily' || $period === 'weekly') && $start && $end) {
            // Detailed Excel for Daily or Weekly
            $items = $this->getDetailedSales($start, $end);
            $data = [
                'items' => $items,
                'date' => $start === $end ? $start : "$start to $end",
            ];
            $filename = "{$period}_sales_report_{$start}.xlsx";

            return Excel::download(new FinancialReportExport($data, $period, true), $filename);
        }

        // Summary Excel (Monthly/Yearly uses P&L structure)
        if ($period === 'monthly' && $start) {
            $dt = Carbon::parse($start);
            $data = ['data' => $this->getMonthlyPandLData($dt->month, $dt->year)];
            $filename = "monthly_financial_report_{$dt->format('Y_m')}.xlsx";
        } elseif ($period === 'yearly' && $start) {
            $year = Carbon::parse($start)->year;
            $data = ['data' => $this->getYearlyPandLData($year)];
            $filename = "yearly_financial_report_{$year}.xlsx";
        } else {
            $data = $this->generateReportData($period, $start, $end);
            $filename = $start ? "financial_report_{$start}_to_{$end}.xlsx" : "financial_report_all_{$period}.xlsx";
        }

        return Excel::download(new FinancialReportExport($data, $period), $filename);
    }

    public function exportFinancialPdf(Request $request)
    {
        $period = $request->input('period', 'daily');
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        $system = DB::table('system')->first();
        $systemName = $system->systemname ?? config('app.name');
        $systemLogoUrl = $system->systemlogo ?? null;
        $systemLogoPath = null;

        if ($systemLogoUrl) {
            $parsedUrl = parse_url($systemLogoUrl);
            $path = $parsedUrl['path'] ?? '';
            
            // Handle /POS/ subdirectory prefix
            if (str_starts_with($path, '/POS/')) {
                $path = substr($path, 4);
            }
            
            $localPath = public_path($path);
            if (file_exists($localPath)) {
                $systemLogoPath = $localPath;
            }
        }

        if ($start && $end && ($period === 'daily' || $period === 'weekly')) {
            $items = $this->getDetailedSales($start, $end);
            $html = view('exports.daily_detailed_pdf', [
                'items' => $items,
                'date' => $start === $end ? Carbon::parse($start)->format('d F Y') : Carbon::parse($start)->format('d M').' - '.Carbon::parse($end)->format('d F Y'),
                'systemName' => $systemName,
                'systemLogo' => $systemLogoPath,
                'title' => strtoupper($period).' SALES REPORT',
            ])->render();
            TCPDF::SetTitle(ucfirst($period)." Sales Report - $start");
        } elseif (($period === 'monthly' || $period === 'yearly') && $start) {
            // P&L Report matching screenshot
            $date = Carbon::parse($start);
            $pnlData = $period === 'yearly'
                ? $this->getYearlyPandLData($date->year)
                : $this->getMonthlyPandLData($date->month, $date->year);

            $html = view('exports.monthly_pdf', [
                'data' => $pnlData,
                'systemName' => $systemName,
                'systemLogo' => $systemLogoPath,
            ])->render();

            $title = $period === 'yearly' ? 'Yearly Profit & Loss - ' : 'Monthly Profit & Loss - ';
            TCPDF::SetTitle($title.$pnlData['month_label']);
        } else {
            // Summary Report
            $data = $this->generateReportData($period, $start, $end);
            $statsRangeStart = $start ? Carbon::parse($start) : Carbon::parse('2026-05-29');
            $statsRangeEnd = $end ? Carbon::parse($end) : now();

            $html = view('exports.financial_pdf', [
                'data' => $data,
                'period' => $period,
                'stats' => $this->getFinancialDataForRange($statsRangeStart, $statsRangeEnd),
                'is_single' => (bool) $start,
                'systemName' => $systemName,
                'systemLogo' => $systemLogoPath,
            ])->render();

            TCPDF::SetTitle('Financial Report - '.ucfirst($period));
        }

        TCPDF::AddPage();
        TCPDF::writeHTML($html, true, false, true, false, '');
        $filename = $start ? "financial_report_{$start}.pdf" : "financial_report_{$period}.pdf";

        return response(TCPDF::Output($filename, 'S'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function printReport(Request $request)
    {
        $period = $request->input('period', 'daily');
        $start = $request->input('start_date');

        $system = DB::table('system')->first();
        $systemName = $system->systemname ?? config('app.name');
        $systemLogo = $system->systemlogo ?? null;

        if (($period === 'daily' || $period === 'weekly') && $start) {
            $end = $request->input('end_date');
            $items = $this->getDetailedSales($start, $end);

            return view('exports.daily_print', [
                'items' => $items,
                'date' => $start === $end ? $start : "$start to $end",
                'systemName' => $systemName,
                'systemLogo' => $systemLogo,
                'title' => strtoupper($period).' SALES REPORT',
            ]);
        } elseif ($period === 'monthly' && $start) {
            $date = Carbon::parse($start);
            $pnlData = $this->getMonthlyPandLData($date->month, $date->year);

            return view('exports.monthly_print', [
                'data' => $pnlData,
                'systemName' => $systemName,
                'systemLogo' => $systemLogo,
            ]);
        } elseif ($period === 'yearly' && $start) {
            $date = Carbon::parse($start);
            $pnlData = $this->getYearlyPandLData($date->year);

            return view('exports.monthly_print', [
                'data' => $pnlData,
                'systemName' => $systemName,
                'systemLogo' => $systemLogo,
            ]);
        }

        return 'Print for this period is not yet supported in browser mode. Please use PDF.';
    }
}
