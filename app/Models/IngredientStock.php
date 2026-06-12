<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class IngredientStock extends Model
{
    use BelongsToBranch;

    protected $table = 'ingredient_stocks';

    protected $primaryKey = 'ingredientstock_id';

    public $timestamps = false;

    protected $fillable = [
        'ingredientid',
        'branchid',
        'qty',
        'unit',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredientid', 'ingredientid');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }
}
