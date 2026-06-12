<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class ItemLog extends Model
{
    use BelongsToBranch;

    protected $table = 'item_logs';

    protected $primaryKey = 'itemlogid';

    protected $fillable = [
        'itemid',
        'branchid',
        'type',
        'qty',
        'balance',
        'unit',
        'price',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'itemid', 'itemid');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }
}
