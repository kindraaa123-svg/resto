<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'districts';

    protected $primaryKey = 'districtid';

    public $timestamps = false;

    protected $fillable = ['name', 'provinceid', 'cityid'];
}
