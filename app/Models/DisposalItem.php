<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisposalItem extends Model
{
    protected $table = 'disposal_items';

    protected $primaryKey = 'diposeitem_id';

    protected $fillable = [
        'itemstock_id',
        'qty',
        'reason',
        'evidence',
        'status',
        'created_by',
        'approved_by',
    ];

    public function itemStock()
    {
        return $this->belongsTo(ItemStock::class, 'itemstock_id', 'itemstock_id');
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
