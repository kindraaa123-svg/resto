<?php

namespace App\Http\Controllers;

use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class SystemController extends Controller
{
    public function index()
    {
        $system = System::first();

        return Inertia::render('System/Settings', [
            'systemConfig' => $system,
            'logo_url' => $system->getFirstMediaUrl('logo'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'systemname' => 'required|string|max:255',
            'systemaddress' => 'nullable|string|max:255',
            'systemmanager' => 'nullable|string|max:255',
            'systemcontact' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        $system = System::first();
        $system->update($request->only([
            'systemname',
            'systemaddress',
            'systemmanager',
            'systemcontact',
        ]));

        if ($request->hasFile('logo')) {
            $system->clearMediaCollection('logo');
            $system->addMediaFromRequest('logo')
                ->toMediaCollection('logo', 'system_uploads');

            // Sync path to table
            $system->update(['systemlogo' => $system->getFirstMediaUrl('logo')]);
        }

        return Redirect::back()->with('success', 'System settings updated successfully.');
    }
}
