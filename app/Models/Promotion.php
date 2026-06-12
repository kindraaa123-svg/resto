<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'promotions';

    protected $primaryKey = 'promotionid';

    protected $fillable = [
        'name',
        'type',
        'discount_value',
        'buy_qty',
        'get_qty',
        'buy_menuid',
        'get_menuid',
        'min_purchase',
        'branchids',
        'menuids',
        'menu_addons',
        'status',
        'datefrom',
        'dateto',
        'timefrom',
        'timeto',
        'days',
        'deleted_by',
    ];

    protected $casts = [
        'days' => 'array',
        'branchids' => 'array',
        'menuids' => 'array',
        'menu_addons' => 'array',
        'discount_value' => 'float',
        'min_purchase' => 'float',
        'buy_qty' => 'integer',
        'get_qty' => 'integer',
    ];

    public function buyMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'buy_menuid', 'menuid');
    }

    public function getMenu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'get_menuid', 'menuid');
    }

    protected function getImpactRelations(): array
    {
        return [];
    }
}
