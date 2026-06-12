<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'payrolls';

    protected $primaryKey = 'payrollid';

    public $timestamps = false; // Based on schema, only created_at exists, but let's check schema again

    protected $fillable = [
        'userid',
        'month',
        'total_salary',
        'bonus',
        'deduction',
        'created_at',
        'deleted_by',
    ];

    protected $casts = [
        'month' => 'date',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    protected function getImpactRelations(): array
    {
        return [];
    }
}
