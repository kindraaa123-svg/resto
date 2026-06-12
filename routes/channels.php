<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    if ((int) $user->branchid === (int) $branchId) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    return false;
});
