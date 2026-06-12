<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Menu extends Model implements HasMedia
{
    use InteractsWithMedia, RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'menus';

    protected $primaryKey = 'menuid';

    public $timestamps = false;

    protected $fillable = [
        'picture',
        'name',
        'description',
        'price',
        'categoryid',
        'deleted_by',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoryid', 'categoryid');
    }

    public function addons()
    {
        return $this->hasMany(Addon::class, 'menuid', 'menuid');
    }

    protected function getImpactRelations(): array
    {
        return [
            'addons' => 'Specific Add-ons',
        ];
    }
}
