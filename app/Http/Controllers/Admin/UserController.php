<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreUserRequest;
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
            ->paginate(10)
            ->withQueryString();

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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Str::password(16),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']->value);

        return redirect()
            ->route('admin.users.index', ['locale' => $request->route('locale')])
            ->with('message', 'User account created successfully.');
    }
}
