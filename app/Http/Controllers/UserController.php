<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->role($request->input('role'));
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
                'total'   => User::count(),
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
}
