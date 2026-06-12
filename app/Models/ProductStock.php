<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $table = 'product_stocks';

    protected $primaryKey = 'productstock_id';

    protected $fillable = [
        'productid',
        'branchid',
        'stock',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productid', 'productid');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }
}
