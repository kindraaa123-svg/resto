<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::class)
            ->with(['roles', 'branch'])
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'LIKE', "%{$value}%")
                          ->orWhere('email', 'LIKE', "%{$value}%")
                          ->orWhere('phonenumber', 'LIKE', "%{$value}%");
                    });
                }),
            ])
            ->defaultSort('-id')
            ->paginate(15)
            ->through(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phonenumber' => $u->phonenumber,
                'branchid' => $u->branchid,
                'branch_name' => $u->branch->branchname ?? 'N/A',
                'role' => $u->roles->first()?->name ?? 'N/A',
                'roleid' => $u->roles->first()?->id ?? null,
                'impact' => $u->getDeletionImpact(),
            ])
            ->withQueryString();

        $roles = Role::where('name', '!=', 'Superadmin')->get();
        $branches = Branch::all();

        return Inertia::render('Master/Users', [
            'users' => $users,
            'roles' => $roles,
            'branches' => $branches,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phonenumber' => 'nullable|string|max:50',
            'roleid' => ['nullable', 'exists:roles,id', function ($attribute, $value, $fail) {
                if ($value == 1) {
                    $fail('Cannot create another Superadmin.');
                }
            }],
            'branchid' => ['required_unless:roleid,1', 'nullable', 'exists:branches,branchid'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phonenumber' => $request->phonenumber,
            'password' => Hash::make('password'),
            'roleid' => $request->roleid,
            'branchid' => $request->roleid == 1 ? null : $request->branchid,
        ]);

        return back()->with('success', 'User created successfully with default password password.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phonenumber' => 'nullable|string|max:50',
            'roleid' => ['nullable', 'exists:roles,id', function ($attribute, $value, $fail) use ($user) {
                if ($value == 1 && $user->roleid != 1) {
                    $fail('Cannot promote user to Superadmin.');
                }
                if ($user->roleid == 1 && $value != 1) {
                    $superadminCount = User::role('Superadmin')->count();
                    if ($superadminCount <= 1) {
                        $fail('Cannot demote the only Superadmin.');
                    }
                }
            }],
            'branchid' => ['required_unless:roleid,1', 'nullable', 'exists:branches,branchid'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phonenumber' => $request->phonenumber,
            'roleid' => $request->roleid,
            'branchid' => $request->roleid == 1 ? null : $request->branchid,
        ]);

        return back()->with('success', 'User updated successfully.');
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('password'),
        ]);

        return back()->with('success', 'Password reset to password successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function updateFaceData(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|array',
            'face_descriptor.*' => 'numeric',
        ]);

        $user = auth()->user();
        $user->update([
            'face_descriptor' => $request->face_descriptor,
        ]);

        return response()->json(['message' => 'Face data updated successfully.']);
    }
}
