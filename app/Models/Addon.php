<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Addon extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'addons';

    protected $primaryKey = 'addonid';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'price',
        'categoryid',
        'menuid',
        'deleted_by',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoryid', 'categoryid');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menuid', 'menuid');
    }

    protected function getImpactRelations(): array
    {
        return [];
    }
}
