<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';

    protected $primaryKey = 'cityid';

    public $timestamps = false;

    protected $fillable = ['name', 'provinceid'];
}
