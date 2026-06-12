<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddon extends Model
{
    protected $table = 'order_addons';

    protected $primaryKey = 'orderaddonid';

    public $timestamps = false;

    protected $fillable = [
        'detailorderid',
        'addonid',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(OrderDetail::class, 'detailorderid', 'detailorderid');
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class, 'addonid', 'addonid');
    }
}
