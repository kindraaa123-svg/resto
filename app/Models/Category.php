<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'categories';

    protected $primaryKey = 'categoryid';

    public $timestamps = false;

    protected $fillable = [
        'categoryname',
        'deleted_by',
    ];

    public function menus()
    {
        return $this->hasMany(Menu::class, 'categoryid', 'categoryid');
    }

    public function addons()
    {
        return $this->hasMany(Addon::class, 'categoryid', 'categoryid');
    }

    protected function getImpactRelations(): array
    {
        return [
            'menus' => 'Menu Items',
            'addons' => 'Add-ons',
        ];
    }
}
