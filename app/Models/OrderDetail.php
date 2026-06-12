<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderDetail extends Model
{
    protected $table = 'order_details';

    protected $primaryKey = 'detailorderid';

    public $timestamps = false;

    protected $fillable = [
        'menuid',
        'packagename_id',
        'productid',
        'quantity',
        'note',
        'is_free',
        'orderid',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'orderid', 'orderid');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menuid', 'menuid');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'packagename_id', 'packagename_id');
    }

    public function packageDefinition(): BelongsTo
    {
        // This links to the 'packages' table (Promo model) to get the bundled price
        return $this->belongsTo(Promo::class, 'packagename_id', 'packagename_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productid', 'productid');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(OrderAddon::class, 'detailorderid', 'detailorderid');
    }
}
