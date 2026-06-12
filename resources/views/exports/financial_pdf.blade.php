<!DOCTYPE html>
<html>
<head>
    <title>Financial Report - {{ $is_single ? $data[0]['date_label'] : ucfirst($period) }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        .stats { margin-bottom: 30px; }
        .stats-table { width: 100%; border-collapse: collapse; }
        .stats-table td { padding: 15px; border: 1px solid #eee; }
        .label { font-weight: bold; font-size: 9px; text-transform: uppercase; color: #666; margin-bottom: 5px; }
        .value { font-size: 18px; font-weight: bold; }
        .main-table { width: 100%; border-collapse: collapse; }
        .main-table th { background: #f9f9f9; padding: 10px; text-align: left; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #333; }
        .main-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .profit-positive { color: #059669; }
        .profit-negative { color: #dc2626; }
        .footer { margin-top: 50px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Report</h1>
        <p>
            @if($is_single)
                Period: <strong>{{ $data[0]['date_label'] }}</strong>
            @else
                {{ ucfirst($period) }} Breakdown | Since {{ date('d M Y', strtotime('2026-05-29')) }}
            @endif
        </p>
        <p style="font-size: 10px; color: #666;">Generated on {{ date('d M Y H:i') }}</p>
    </div>

    <div class="stats">
        <table class="stats-table">
            <tr>
                <td width="33%">
                    <div class="label">Total Income</div>
                    <div class="value">Rp {{ number_format($stats['income'], 0, ',', '.') }}</div>
                </td>
                <td width="33%">
                    <div class="label">Total Outcome</div>
                    <div class="value" style="color: #dc2626;">Rp {{ number_format($stats['total_outcome'], 0, ',', '.') }}</div>
                </td>
                <td width="34%">
                    <div class="label">Net Profit</div>
                    <div class="value {{ $stats['profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                        Rp {{ number_format($stats['profit'], 0, ',', '.') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if(!$is_single)
    <table class="main-table">
        <thead>
            <tr>
                <th>Period</th>
                <th align="right">Income</th>
                <th align="right">Outcome</th>
                <th align="right">Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                <td><strong>{{ $row['date_label'] }}</strong></td>
                <td align="right">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                <td align="right">Rp {{ number_format($row['total_outcome'], 0, ',', '.') }}</td>
                <td align="right" class="{{ $row['profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                    Rp {{ number_format($row['profit'], 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="margin-top: 20px; padding: 20px; background: #fcfcfc; border: 1px solid #eee; border-radius: 10px;">
        <h3 style="text-transform: uppercase; font-size: 11px; color: #666; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Detailed Breakdown for {{ $data[0]['date_label'] }}</h3>
        <table width="100%" cellpadding="5">
            <tr>
                <td width="50%" style="color: #666;">Gross Income</td>
                <td width="50%" align="right"><strong>Rp {{ number_format($data[0]['income'], 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td style="color: #666;">Operational Expenses</td>
                <td align="right" style="color: #dc2626;">- Rp {{ number_format($data[0]['operational'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="color: #666;">Payroll / Salaries</td>
                <td align="right" style="color: #dc2626;">- Rp {{ number_format($data[0]['payroll'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td style="color: #666;">Stock Purchases</td>
                <td align="right" style="color: #dc2626;">- Rp {{ number_format($data[0]['stock'], 0, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 1px solid #333;">
                <td><strong>Total Outcome</strong></td>
                <td align="right"><strong style="color: #dc2626;">Rp {{ number_format($data[0]['total_outcome'], 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td><br><strong>NET PROFIT</strong></td>
                <td align="right"><br><strong style="font-size: 16px;" class="{{ $data[0]['profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">Rp {{ number_format($data[0]['profit'], 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This is a computer-generated report and is valid without a signature.</p>
    </div>
</body>
</html>
