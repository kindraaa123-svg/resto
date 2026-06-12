<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'products';

    protected $primaryKey = 'productid';

    protected $fillable = [
        'barcode',
        'productname',
        'price',
        'deleted_by',
    ];

    protected $casts = [];

    protected function getImpactRelations(): array
    {
        return [
            'stocks' => 'Product Stocks',
            'logs' => 'Product Logs',
        ];
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class, 'productid', 'productid');
    }

    public function logs()
    {
        return $this->hasMany(ProductLog::class, 'productid', 'productid');
    }
}
