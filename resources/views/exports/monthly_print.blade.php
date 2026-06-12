<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Statement - {{ $data['month_label'] }}</title>
    <style>
        @media print {
            @page { margin: 1.5cm; }
            body { -webkit-print-color-adjust: exact; }
        }
        
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 13px; 
            color: #000; 
            margin: 0; 
            padding: 40px; 
            background: #fff;
            line-height: 1.6;
        }

        .container { width: 100%; max-width: 800px; margin: 0 auto; }

        .header { text-align: center; margin-bottom: 40px; font-family: Arial, Helvetica, sans-serif; }
        .header img { height: 60px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 28px; font-weight: bold; }
        .header .subtitle { font-size: 18px; margin-top: 5px; }
        .header .date { font-size: 16px; margin-top: 5px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px 12px; text-align: left; }
        
        .col-code { width: 15%; text-align: center; }
        .col-desc { width: 45%; }
        .col-sub { width: 20%; text-align: right; }
        .col-total { width: 20%; text-align: right; }

        .section-header { font-weight: bold; background-color: #f8f8f8; text-transform: uppercase; }
        .total-row { font-weight: bold; }
        .grand-total { font-weight: bold; background-color: #ffffff !important; } /* Changed from yellow to white */

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .print-controls { margin-bottom: 20px; text-align: right; }
        @media print { .print-controls { display: none; } }
        button { padding: 10px 20px; background: #000; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        @if(!isset($is_pdf))
        <div class="print-controls">
            <button onclick="window.print()">Click to Print</button>
        </div>
        @endif

        <div class="header">
            @if($systemLogo)
                <img src="{{ $systemLogo }}" alt="Logo">
            @endif
            <h1>Profit & Loss Statement</h1>
            <div class="subtitle">{{ $systemName }}</div>
            <div class="date">{{ $data['month_label'] }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-code">Account No</th>
                    <th class="col-desc">Description</th>
                    <th class="col-sub">Subtotal</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <!-- REVENUE SECTION -->
                @php $totalRevenue = 0; @endphp
                @foreach($data['revenue'] as $rev)
                    @php $totalRevenue += $rev['amount']; @endphp
                    <tr>
                        <td class="col-code">{{ $rev['code'] }}</td>
                        <td>{{ $rev['label'] }}</td>
                        <td class="text-right">{{ $rev['amount'] > 0 ? number_format($rev['amount'], 2, ',', '.') : '-' }}</td>
                        <td class="text-right"></td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-center">Total Revenue</td>
                    <td></td>
                    <td class="text-right">{{ number_format($totalRevenue, 2, ',', '.') }}</td>
                </tr>

                <!-- EMPTY ROW -->
                <tr><td colspan="4" style="border: none; height: 20px;"></td></tr>

                <!-- EXPENSE SECTION -->
                @php $totalExpense = 0; @endphp
                @foreach($data['expenses'] as $exp)
                    @php $totalExpense += $exp['amount']; @endphp
                    <tr>
                        <td class="col-code">{{ $exp['code'] }}</td>
                        <td>{{ $exp['label'] }}</td>
                        <td class="text-right">({{ $exp['amount'] > 0 ? number_format($exp['amount'], 2, ',', '.') : '-' }})</td>
                        <td class="text-right"></td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-center">Total Expenses</td>
                    <td class="text-right">({{ number_format($totalExpense, 2, ',', '.') }})</td>
                    <td></td>
                </tr>

                <!-- GRAND TOTAL -->
                @php $netProfit = $totalRevenue - $totalExpense; @endphp
                <tr class="grand-total">
                    <td colspan="2" class="text-center">NET PROFIT / LOSS</td>
                    <td></td>
                    <td class="text-right">{{ number_format($netProfit, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @if(!isset($is_pdf))
    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
    @endif
</body>
</html>
