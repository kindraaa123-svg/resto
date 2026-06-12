<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use BelongsToBranch;

    protected $table = 'orders';

    protected $primaryKey = 'orderid';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'tableid',
        'ordertime',
        'completiontime',
        'paymentid',
        'branchid',
        'ordertype',
        'status',
        'totalprice',
        'note',
        'applied_promotion_id',
        'applied_promotion_name',
        'applied_promotion_discount',
    ];

    protected function casts(): array
    {
        return [
            'ordertime' => 'datetime',
        ];
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class, 'orderid', 'orderid');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'orderid', 'orderid');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }

    public function tableSeat(): BelongsTo
    {
        return $this->belongsTo(TableSeat::class, 'tableid', 'tableseatid');
    }
}
