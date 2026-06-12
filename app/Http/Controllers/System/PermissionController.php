<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index()
    {
        // Auto-seed basic permissions if empty (just to ensure there's data for the matrix)
        if (Permission::count() === 0) {
            $defaultPermissions = [
                'dashboard.view',
                'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
                'payments.view', 'payments.create',
                'kitchen.view', 'kitchen.manage',
                'menu.view', 'menu.create', 'menu.edit', 'menu.delete',
                'ingredient.ingredients.view', 'ingredient.ingredient-stocks.view', 'ingredient.ingredient-logs.view',
                'item.items.view', 'item.item-stocks.view', 'item.item-logs.view',
                'finance.reports.view', 'finance.salaries.view', 'finance.operationals.view',
                'master.categories.view', 'master.addons.view', 'master.products.view', 'master.freebies.view', 'master.packages.view', 'master.promotions.view',
                'master.users.view', 'master.branches.view', 'master.tables.view',
                'system.permissions.manage', 'system.activity-log.view', 'system.backup.manage', 'system.settings.manage',
            ];

            foreach ($defaultPermissions as $perm) {
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            }
        }

        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();

        return Inertia::render('System/Permissions', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*.id' => 'required|exists:roles,id',
            'roles.*.permissions' => 'present|array',
        ]);

        foreach ($request->roles as $roleData) {
            $role = Role::findById($roleData['id']);
            $role->syncPermissions($roleData['permissions']);
        }

        // Clear Spatie's permission cache to ensure changes take effect immediately
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('success', 'Permissions updated successfully.');
    }
}
