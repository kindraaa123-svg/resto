<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'packages';

    protected $primaryKey = 'packageid';

    public $timestamps = false;

    protected $fillable = [
        'packagename_id',
        'menuid',
        'freeid',
        'branchid',
        'qty',
        'price',
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
    ];

    public function package()
    {
        return $this->belongsTo(Package::class, 'packagename_id', 'packagename_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menuid', 'menuid');
    }

    public function addons()
    {
        return $this->belongsToMany(Addon::class, 'package_addons', 'package_id', 'addon_id');
    }

    public function promoFreebie()
    {
        return $this->belongsTo(PromoFreebie::class, 'freeid', 'freeid');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }

    protected function getImpactRelations(): array
    {
        return [];
    }
}
