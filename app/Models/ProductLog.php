<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLog extends Model
{
    protected $table = 'product_logs';

    protected $primaryKey = 'productlog_id';

    public $timestamps = false; // It has created_at but maybe not updated_at based on DESCRIBE

    protected $fillable = [
        'productid',
        'branchid',
        'qty',
        'balance',
        'type',
        'price',
        'created_at',
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
