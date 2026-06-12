<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class ItemStock extends Model
{
    use BelongsToBranch;

    protected $table = 'item_stocks';

    protected $primaryKey = 'itemstock_id';

    protected $fillable = [
        'itemid',
        'branchid',
        'qty',
        'unit',
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
