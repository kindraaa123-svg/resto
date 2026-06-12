<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    protected static function bootBelongsToBranch()
    {
        static::addGlobalScope('branch', function (Builder $builder) {
            if (Auth::check() && Auth::user()->branchid) {
                $builder->where($builder->getQuery()->from.'.branchid', Auth::user()->branchid);
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->branchid && ! $model->branchid) {
                $model->branchid = Auth::user()->branchid;
            }
        });
    }
}
