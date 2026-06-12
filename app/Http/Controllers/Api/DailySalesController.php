<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DailySalesController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->format('Y-m-d'));

        $orders = Order::whereDate('ordertime', $date)
            ->where('status', '!=', 'Cancelled') // Exclude cancelled orders
            ->with(['details.menu', 'payment'])
            ->get();

        $reportData = [];
        $totalHargaJual = 0;
        $totalHpp = 0;
        $totalUntung = 0;

        foreach ($orders as $order) {
            $paymentMethod = $order->payment?->method ?? 'Cash';
            foreach ($order->details as $detail) {

                // Calculate dynamic COGS
                $cogsPerItem = 0;

                $hargaJualPerItem = (float) ($detail->menu->price ?? 0);
                $jumlah = (int) ($detail->quantity ?? 0);

                $totalHargaJualRow = $hargaJualPerItem * $jumlah;
                $totalCogsForDetail = (float) $cogsPerItem * $jumlah;

                // Fix: Correctly calculate profit based on total row price and total row COGS
                $untung = $totalHargaJualRow - $totalCogsForDetail;

                \Log::info('DailySales Debug', [
                    'menu' => $detail->menu->name,
                    'pricePerItem' => $hargaJualPerItem,
                    'qty' => $jumlah,
                    'totalPrice' => $totalHargaJualRow,
                    'cogsPerItem' => $cogsPerItem,
                    'totalCogs' => $totalCogsForDetail,
                    'profit' => $untung,
                ]);

                $reportData[] = [
                    'waktu' => $order->ordertime ? $order->ordertime->format('H.i') : '-',
                    'barang' => $detail->menu->name ?? 'Unknown',
                    'jumlah' => $jumlah,
                    'harga_jual' => $totalHargaJualRow, // Display total price for the row
                    'pembayaran' => $paymentMethod,
                    'hpp' => $totalCogsForDetail, // Display total COGS for the row
                    'untung' => $untung,
                ];

                $totalHargaJual += $totalHargaJualRow;
                $totalHpp += $totalCogsForDetail;
                $totalUntung += $untung;
            }
        }

        return response()->json([
            'date' => $date,
            'items' => $reportData,
            'totals' => [
                'harga_jual' => $totalHargaJual,
                'hpp' => $totalHpp,
                'untung' => $totalUntung,
            ],
        ]);
    }
}
