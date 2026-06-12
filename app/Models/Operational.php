<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operational extends Model
{
    use BelongsToBranch, RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'operationals';

    protected $primaryKey = 'operationalid';

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'branchid',
        'name',
        'amount',
        'category',
        'date',
        'created_at',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }

    protected function getImpactRelations(): array
    {
        return [];
    }
}
