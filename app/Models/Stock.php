<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $table = 'stock';

    protected $primaryKey = 'stockid';

    public $timestamps = false;

    protected $fillable = [
        'menuid',
        'addonid',
        'branchid',
        'status',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menuid', 'menuid');
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class, 'addonid', 'addonid');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }
}
