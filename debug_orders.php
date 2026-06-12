<?php
include 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

$orders = Order::withoutGlobalScopes()
    ->where(function($q) {
        $q->whereIn('status', ['Pending', 'Cooking', ''])
          ->orWhereNull('status');
    })
    ->get(['orderid', 'status', 'completiontime', 'ordertime']);

echo "Total potential orders: " . $orders->count() . "\n";
foreach ($orders as $order) {
    echo "ID: {$order->orderid} | Status: [{$order->status}] | CompTime: [{$order->completiontime}] | OrderTime: {$order->ordertime}\n";
}

$pendingOrders = Order::withoutGlobalScopes()
    ->where(function($q) {
        $q->whereIn('status', ['Pending', 'Cooking', ''])
          ->orWhereNull('status');
    })
    ->where(function($q) {
        $q->whereNull('completiontime')
          ->orWhere('completiontime', 0);
    })
    ->get(['orderid', 'status', 'completiontime', 'ordertime']);

echo "\nFiltered orders (Kitchen Logic): " . $pendingOrders->count() . "\n";
foreach ($pendingOrders as $order) {
    echo "ID: {$order->orderid} | Status: [{$order->status}] | CompTime: [{$order->completiontime}] | OrderTime: {$order->ordertime}\n";
}
