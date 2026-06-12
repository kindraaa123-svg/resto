<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TableSeat extends Model
{
    use BelongsToBranch, RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'tableseats';

    protected $primaryKey = 'tableseatid';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'barcode',
        'branchid',
        'deleted_by',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'tableid', 'tableseatid');
    }

    protected function getImpactRelations(): array
    {
        return [
            'orders' => 'Order History',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($table) {
            if (empty($table->barcode)) {
                $table->barcode = static::generateUniqueBarcode();
            }
        });
    }

    public static function generateUniqueBarcode(): string
    {
        do {
            $barcode = 'TBL-'.strtoupper(Str::random(10));
        } while (static::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }
}
