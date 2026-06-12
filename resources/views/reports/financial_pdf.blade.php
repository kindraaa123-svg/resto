<!DOCTYPE html>
<html>
<head>
    <title>REPORT - {{ $isDaily ? $date : $period }}</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { text-align: center; background-color: #f2f2f2; }
        .header { text-align: center; font-weight: bold; margin-bottom: 20px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        @if($system && $system->systemlogo)
            <img src="{{ public_path(parse_url($system->systemlogo, PHP_URL_PATH)) }}" height="50" style="margin-bottom: 10px;" alt="Logo" /><br>
        @endif
        <h2>{{ $isDaily ? 'DAILY SALES REPORT' : 'FINANCIAL REPORT' }}</h2>
        <h3>{{ $isDaily ? $date : 'Period: ' . ucfirst($period) }}</h3>
        <h4>{{ $system->systemname ?? 'Joyi' }}</h4>
    </div>
    
    <table>
        <thead>
            <tr>
                @if($isDaily)
                    <th>Time</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Payment</th>
                    <th>COGS</th>
                    <th>Profit</th>
                @else
                    <th>Period</th>
                    <th>Income</th>
                    <th>Outcome</th>
                    <th>Net Profit</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @if($isDaily)
                @foreach($items as $row)
                    <tr>
                        <td class="text-center">{{ $row['waktu'] }}</td>
                        <td>{{ $row['barang'] }}</td>
                        <td class="text-center">{{ $row['jumlah'] }}</td>
                        <td class="text-right">Rp {{ number_format($row['harga_jual'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ $row['pembayaran'] }}</td>
                        <td class="text-right">Rp {{ number_format($row['hpp'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($row['untung'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                @foreach($data as $row)
                    <tr>
                        <td>{{ $row['date'] }}</td>
                        <td class="text-right">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($row['total_outcome'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($row['profit'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        @if($isDaily)
        <tfoot>
            <tr>
                <td colspan="3" class="font-bold text-center">TOTAL SALES</td>
                <td class="font-bold text-right">Rp {{ number_format($totals['harga_jual'], 0, ',', '.') }}</td>
                <td colspan="1"></td>
                <td class="font-bold text-right">Rp {{ number_format($totals['hpp'], 0, ',', '.') }}</td>
                <td class="font-bold text-right">Rp {{ number_format($totals['untung'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
    
    <div style="margin-top: 20px; font-size: 10px; text-align: right;">
        Printed at: {{ $generatedAt }}
    </div>
</body>
</html>