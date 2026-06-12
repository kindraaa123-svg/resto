<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestItem extends Model
{
    protected $table = 'request_items';

    protected $primaryKey = 'reqitem_id';

    public $timestamps = false;

    protected $fillable = [
        'itemid',
        'description',
        'qty',
        'status',
        'requested_by',
        'approved_by',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'itemid', 'itemid');
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
