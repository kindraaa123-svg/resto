<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'package_names';

    protected $primaryKey = 'packagename_id';

    public $timestamps = false;

    protected $fillable = [
        'packagename',
        'deleted_by',
    ];

    public function promos()
    {
        return $this->hasMany(Promo::class, 'packagename_id', 'packagename_id');
    }

    protected function getImpactRelations(): array
    {
        return [
            'promos' => 'Related Promos',
        ];
    }
}
