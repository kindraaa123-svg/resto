<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestIngredient extends Model
{
    protected $table = 'request_ingredients';

    protected $primaryKey = 'reqingredient_id';

    protected $fillable = [
        'ingredientid',
        'branchid',
        'qty',
        'unit',
        'description',
        'status',
        'requested_by',
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

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
