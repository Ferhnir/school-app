<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Enums\UserRole;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $query = User::query();

        if ($request->filled('role')) {
            if ($request->input('role') === 'no_roles') {
                $query->whereDoesntHave('roles');
            } else {
                $query->role($request->input('role'));
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where('name', 'like', "%{$search}%");
        }

        $users = $query
            ->with('roles')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'suspended_at' => $user->suspended_at,
                'role' => $user->roles->first()?->name,
            ]);

        return Inertia::render('UsersPanel', [
            'users_counts' => [
                'total' => User::count(),
                UserRole::ADMIN->value => User::role(UserRole::ADMIN->value)->count(),
                UserRole::TEACHER->value => User::role(UserRole::TEACHER->value)->count(),
                UserRole::PARENT->value => User::role(UserRole::PARENT->value)->count(),
                'no_roles' => User::query()->whereDoesntHave('roles')->count(),
            ],
            'users' => $users,
            'roles' => Role::all(['name']),
            'filters' => [
                'search' => $request->input('search'),
                'role' => $request->input('role'),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Str::password(16),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return redirect()
            ->route('admin.users.index', ['locale' => app()->getLocale()])
            ->with('message', 'User account created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index', ['locale' => app()->getLocale()])
            ->with('message', 'User updated successfully.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()
                ->back()
                ->with('error', 'You cannot suspend your own account.');
        }

        $wasSuspended = $user->suspended_at !== null;

        $user->update([
            'suspended_at' => $wasSuspended ? null : now(),
        ]);

        $message = $wasSuspended
            ? 'User account reactivated.'
            : 'User account suspended.';

        return redirect()
            ->back()
            ->with('message', $message);
    }
}
