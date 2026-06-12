<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisposalIngredient extends Model
{
    protected $table = 'disposal_ingredients';

    protected $primaryKey = 'disposeingredient_id';

    protected $fillable = [
        'ingredientid',
        'branchid',
        'qty',
        'unit',
        'reason',
        'evidence',
        'status',
        'created_by',
        'approved_by',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredientid', 'ingredientid');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branchid', 'branchid');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
