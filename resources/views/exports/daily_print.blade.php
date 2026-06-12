<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Report - {{ $date }}</title>
    <style>
        @media print {
            @page { margin: 1cm; size: landscape; }
            body { -webkit-print-color-adjust: exact; }
            .print-controls { display: none; }
        }
        
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 12px; 
            color: #000; 
            margin: 0; 
            padding: 20px; 
            background: #fff;
            line-height: 1.4;
        }

        .container { width: 100%; }

        .header { text-align: center; margin-bottom: 30px; font-family: Arial, Helvetica, sans-serif; }
        .header img { height: 60px; margin-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .header .subtitle { font-size: 18px; margin-top: 5px; font-weight: bold; }
        .header .date { font-size: 16px; margin-top: 5px; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 8px 10px; text-align: left; word-wrap: break-word; }
        
        th { 
            font-weight: bold; 
            text-align: center; 
            background-color: #f2f2f2 !important; 
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .total-row { font-weight: bold; background-color: #eee !important; font-family: Arial, Helvetica, sans-serif; }

        .print-controls { margin-bottom: 20px; text-align: right; }
        button { padding: 10px 20px; background: #000; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }

        /* Column Widths */
        .col-time { width: 100px; }
        .col-item { width: auto; }
        .col-qty { width: 80px; }
        .col-price { width: 150px; }
        .col-payment { width: 150px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="print-controls">
            <button onclick="window.print()">Click to Print / Save PDF</button>
        </div>

        <div class="header">
            @if($systemLogo)
                <img src="{{ $systemLogo }}" alt="Logo">
            @endif
            <h1>{{ $title ?? 'DAILY SALES REPORT' }}</h1>
            <div class="subtitle">{{ $systemName }}</div>
            <div class="date">{{ $date }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-time">Time</th>
                    <th class="col-item">Item</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-price">Price</th>
                    <th class="col-payment">Payment</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalSales = 0;
                @endphp
                @foreach($items as $item)
                    @php
                        $totalSales += $item['price'];
                    @endphp
                    <tr>
                        <td class="text-center">{{ str_replace(':', '.', $item['time']) }}</td>
                        <td>{{ $item['item'] }}</td>
                        <td class="text-center">{{ $item['qty'] }}</td>
                        <td class="text-right">Rp{{ number_format($item['price'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ $item['payment'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="text-center">TOTAL SALES</td>
                    <td class="text-right">Rp{{ number_format($totalSales, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
    </script>
</body>
</html>
