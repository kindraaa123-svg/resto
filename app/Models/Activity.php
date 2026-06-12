<?php

namespace App\Models;

use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected static function booted()
    {
        parent::booted();

        static::creating(function ($activity) {
            $activity->ip_address = Request::ip();
            $activity->user_agent = Request::userAgent();
            $activity->latitude = Request::header('X-Latitude') ?? Request::input('latitude');
            $activity->longitude = Request::header('X-Longitude') ?? Request::input('longitude');
        });
    }
}
