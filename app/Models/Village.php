<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $table = 'villages';

    protected $primaryKey = 'villageid';

    public $timestamps = false;

    protected $fillable = ['name', 'provinceid', 'cityid', 'districtid'];
}
