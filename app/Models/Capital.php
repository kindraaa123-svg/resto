<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Capital extends Model
{
    protected $table = 'modal';

    protected $primaryKey = 'modalid';

    public $timestamps = false;

    protected $fillable = [
        'menuid',
        'addonid',
        'price',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menuid', 'menuid');
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class, 'addonid', 'addonid');
    }
}
