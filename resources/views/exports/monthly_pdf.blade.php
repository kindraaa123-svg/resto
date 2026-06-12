<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss - {{ $data['month_label'] }}</title>
    <style>
        body { 
            font-family: "times", serif; 
            font-size: 10pt; 
            color: #000; 
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
        }
        .header h1 { 
            margin: 0; 
            font-size: 18pt; 
            font-weight: bold; 
            font-family: "helvetica", sans-serif;
        }
        .header .subtitle { 
            font-size: 12pt; 
            font-weight: bold; 
            margin-top: 5px;
            font-family: "helvetica", sans-serif;
        }
        .header .date { 
            font-size: 11pt; 
            margin-top: 5px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        th { 
            font-weight: bold; 
            text-align: center;
            background-color: #f2f2f2;
            font-family: "helvetica", sans-serif;
            font-size: 9pt;
            border: 0.5pt solid #000;
        }
        td { 
            font-size: 9pt;
            border: 0.5pt solid #000;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
            font-family: "helvetica", sans-serif;
        }
        .grand-total {
            font-weight: bold;
            background-color: #d9d9d9;
            font-family: "helvetica", sans-serif;
        }
        .negative { color: #ff0000; }
    </style>
</head>
<body>
    <div class="header">
        @if($systemLogo)
            <img src="{{ $systemLogo }}" height="50">
        @endif
        <h1>Profit & Loss Statement</h1>
        <div class="subtitle">{{ $systemName }}</div>
        <div class="date">{{ $data['month_label'] }}</div>
    </div>

    <table border="1" cellpadding="5">
        <thead>
            <tr>
                <th width="15%">Account No</th>
                <th width="45%">Description</th>
                <th width="20%">Subtotal</th>
                <th width="20%">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalRevenue = 0; @endphp
            @foreach($data['revenue'] as $rev)
                @php $totalRevenue += $rev['amount']; @endphp
                <tr>
                    <td width="15%" align="center">{{ $rev['code'] }}</td>
                    <td width="45%">{{ $rev['label'] }}</td>
                    <td width="20%" align="right">{{ $rev['amount'] > 0 ? 'Rp' . number_format($rev['amount'], 0, ',', '.') : '-' }}</td>
                    <td width="20%"></td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" width="60%" align="center">Total Revenue</td>
                <td width="20%"></td>
                <td width="20%" align="right">Rp{{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>

            <tr><td colspan="4" border="0" height="10"></td></tr>

            @php $totalExpense = 0; @endphp
            @foreach($data['expenses'] as $exp)
                @php $totalExpense += $exp['amount']; @endphp
                <tr>
                    <td width="15%" align="center">{{ $exp['code'] }}</td>
                    <td width="45%">{{ $exp['label'] }}</td>
                    <td width="20%" align="right" class="negative">({{ $exp['amount'] > 0 ? 'Rp' . number_format($exp['amount'], 0, ',', '.') : '-' }})</td>
                    <td width="20%"></td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2" width="60%" align="center">Total Expenses</td>
                <td width="20%" align="right" class="negative">(Rp{{ number_format($totalExpense, 0, ',', '.') }})</td>
                <td width="20%"></td>
            </tr>

            @php $netProfit = $totalRevenue - $totalExpense; @endphp
            <tr class="grand-total">
                <td colspan="2" width="60%" align="center">NET PROFIT / LOSS</td>
                <td width="20%"></td>
                <td width="20%" align="right" class="{{ $netProfit < 0 ? 'negative' : '' }}">
                    {{ $netProfit < 0 ? '(' : '' }}Rp{{ number_format(abs($netProfit), 0, ',', '.') }}{{ $netProfit < 0 ? ')' : '' }}
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
