<table>
    <thead>
        <tr><th colspan="7"></th></tr>
        <tr><th colspan="7"></th></tr>
        <tr>
            <th colspan="7" style="text-align: center; font-size: 14pt; font-weight: bold;">{{ $systemName }}</th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center; font-weight: bold;">Date: {{ $date }}</th>
        </tr>
        <tr>
            <th style="background-color: #f2f2f2; border: 1px solid #000; font-weight: bold; text-align: center;">Time</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000; font-weight: bold; text-align: center;">Item</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000; font-weight: bold; text-align: center;">Qty</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000; font-weight: bold; text-align: center;">Price</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000; font-weight: bold; text-align: center;">Payment</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000; font-weight: bold; text-align: center;">COGS</th>
            <th style="background-color: #f2f2f2; border: 1px solid #000; font-weight: bold; text-align: center;">Profit</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalSales = 0;
            $totalCogs = 0;
            $totalProfit = 0;
        @endphp
        @foreach($items as $item)
            @php
                $totalSales += $item['price'];
                $totalCogs += $item['hpp'];
                $totalProfit += $item['profit'];
            @endphp
            <tr>
                <td style="border: 1px solid #000; text-align: center;">{{ str_replace(':', ',', $item['time']) }}</td>
                <td style="border: 1px solid #000;">{{ $item['item'] }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['qty'] }}</td>
                <td style="border: 1px solid #000; text-align: right;">Rp{{ number_format($item['price'], 0, ',', '.') }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $item['payment'] }}</td>
                <td style="border: 1px solid #000; text-align: right;">Rp{{ number_format($item['hpp'], 0, ',', '.') }}</td>
                <td style="border: 1px solid #000; text-align: right;">Rp{{ number_format($item['profit'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="border: 1px solid #000; font-weight: bold; text-align: center;">TOTAL SALES</td>
            <td style="border: 1px solid #000; font-weight: bold; text-align: right;">Rp{{ number_format($totalSales, 0, ',', '.') }}</td>
            <td style="border: 1px solid #000;"></td>
            <td style="border: 1px solid #000; font-weight: bold; text-align: right;">Rp{{ number_format($totalCogs, 0, ',', '.') }}</td>
            <td style="border: 1px solid #000; font-weight: bold; text-align: right;">Rp{{ number_format($totalProfit, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
