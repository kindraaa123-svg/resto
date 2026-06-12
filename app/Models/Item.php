<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'items';

    protected $primaryKey = 'itemid';

    protected $fillable = [
        'itemname',
        'unit',
        'deleted_by',
    ];

    public function stocks()
    {
        return $this->hasMany(ItemStock::class, 'itemid', 'itemid');
    }

    public function itemLogs()
    {
        return $this->hasMany(ItemLog::class, 'itemid', 'itemid');
    }

    protected function getImpactRelations(): array
    {
        return [
            'stocks' => 'Current Stock Records',
            'itemLogs' => 'Item Logs',
        ];
    }
}
