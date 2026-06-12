<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use BelongsToBranch, RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'branches';

    protected $primaryKey = 'branchid';

    public $timestamps = false;

    protected $fillable = [
        'branchname',
        'provincesid',
        'citiesid',
        'districtsid',
        'villagesid',
        'detail_address',
        'longitude',
        'latitude',
        'deleted_by',
    ];

    public function staff()
    {
        return $this->hasMany(User::class, 'branchid', 'branchid');
    }

    public function tables()
    {
        return $this->hasMany(TableSeat::class, 'branchid', 'branchid');
    }

    public function stocks()
    {
        return $this->hasMany(IngredientStock::class, 'branchid', 'branchid');
    }

    public function ingredientLogs(): HasMany
    {
        return $this->hasMany(IngredientLog::class, 'branchid', 'branchid');
    }

    protected function getImpactRelations(): array
    {
        return [
            'staff' => 'Staff Members',
            'tables' => 'Tables',
            'stocks' => 'Ingredient Stocks',
            'inventoryLogs' => 'Inventory Logs',
        ];
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'provincesid', 'provinceid');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'citiesid', 'cityid');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'districtsid', 'districtid');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'villagesid', 'villageid');
    }
}
