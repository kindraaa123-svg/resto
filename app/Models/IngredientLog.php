<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class IngredientLog extends Model
{
    use BelongsToBranch;

    protected $table = 'ingredient_logs';

    protected $primaryKey = 'ingredientlogid';

    public $timestamps = false; // The schema only has created_at

    protected $fillable = [
        'ingredientid',
        'branchid',
        'type', // 'IN' or 'OUT'
        'qty',
        'balance',
        'unit',
        'price',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
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
