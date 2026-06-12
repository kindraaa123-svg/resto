<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $primaryKey = 'paymentid';

    public $timestamps = false;

    protected $fillable = [
        'orderid',
        'paid',
        'changes',
        'method',
        'status',
        'paymentdate',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'orderid', 'orderid');
    }
}
