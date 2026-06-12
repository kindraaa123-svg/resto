<?php

namespace App\Models;

use App\Traits\RecordsDeletion;
use App\Traits\TracksDeletionImpact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use RecordsDeletion, SoftDeletes, TracksDeletionImpact;

    protected $table = 'ingredients';

    protected $primaryKey = 'ingredientid';

    protected $fillable = [
        'name',
        'unit',
        'deleted_by',
    ];

    public function stocks()
    {
        return $this->hasMany(IngredientStock::class, 'ingredientid', 'ingredientid');
    }

    public function ingredientLogs(): HasMany
    {
        return $this->hasMany(IngredientLog::class, 'ingredientid', 'ingredientid');
    }

    protected function getImpactRelations(): array
    {
        return [
            'stocks' => 'Current Stock Records',
            'ingredientLogs' => 'Ingredient Logs',
        ];
    }
}
