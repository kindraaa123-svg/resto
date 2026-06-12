<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer.role')->latest();

        if ($request->filled('role')) {
            $query->whereHas('causer', function ($q) use ($request) {
                $q->where('roleid', $request->role);
            });
        }

        $logs = $query->paginate(15)
            ->withQueryString()
            ->through(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->causer ? $log->causer->name : 'System/Guest',
                    'role' => $log->causer && $log->causer->role ? $log->causer->role->name : null,
                    'action' => $log->description,
                    'subject' => $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : 'System Route',
                    'properties' => $log->properties,
                    'ip_address' => $log->ip_address ?? '-',
                    'latitude' => $log->latitude,
                    'longitude' => $log->longitude,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('System/ActivityLog', [
            'logs' => $logs,
            'roles' => Role::select('id', 'name')->get(),
            'filters' => $request->only(['role']),
        ]);
    }
}
