<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Report - {{ $date }}</title>
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
            font-size: 16pt; 
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

        th, td { 
            border: 0.5pt solid #000; 
            padding: 5pt; 
        }

        th { 
            font-weight: bold; 
            text-align: center;
            background-color: #f2f2f2;
            font-family: "helvetica", sans-serif;
            font-size: 9pt;
        }

        td {
            font-size: 9pt;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
            font-family: "helvetica", sans-serif;
        }

        /* Column Widths for TCPDF */
        .col-time { width: 12%; }
        .col-item { width: 45%; }
        .col-qty { width: 10%; }
        .col-price { width: 18%; }
        .col-payment { width: 15%; }
    </style>
</head>
<body>
    <div class="header">
        @if($systemLogo)
            <img src="{{ $systemLogo }}" height="50">
        @endif
        <h1>{{ $title ?? 'DAILY SALES REPORT' }}</h1>
        <div class="subtitle">{{ $systemName }}</div>
        <div class="date">{{ $date }}</div>
    </div>

    <table border="1" cellpadding="4">
        <thead>
            <tr>
                <th width="12%">Time</th>
                <th width="45%">Item</th>
                <th width="10%">Qty</th>
                <th width="18%">Price</th>
                <th width="15%">Payment</th>
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
                    <td width="12%" align="center">{{ str_replace(':', '.', $item['time']) }}</td>
                    <td width="45%">{{ $item['item'] }}</td>
                    <td width="10%" align="center">{{ $item['qty'] }}</td>
                    <td width="18%" align="right">Rp{{ number_format($item['price'], 0, ',', '.') }}</td>
                    <td width="15%" align="center">{{ $item['payment'] }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f2f2f2;">
                <td colspan="3" width="67%" align="center">TOTAL SALES</td>
                <td width="18%" align="right">Rp{{ number_format($totalSales, 0, ',', '.') }}</td>
                <td width="15%"></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
