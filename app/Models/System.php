<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class System extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'system';

    protected $primaryKey = 'systemid';

    public $timestamps = false;

    protected $fillable = [
        'systemname',
        'systemlogo',
        'systemaddress',
        'systemmanager',
        'systemcontact',
    ];
}
