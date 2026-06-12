<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoFreebie extends Model
{
    protected $table = 'freebies';

    protected $primaryKey = 'freeid';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
