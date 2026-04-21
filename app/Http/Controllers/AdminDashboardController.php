<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enums\UserRole;

class AdminDashboardController extends Controller
{
    public function index(): \Inertia\Response
    {
        return Inertia::render('Dashboard/Admin', [
            'users_counts' => [
                'admins'   => User::role(UserRole::ADMIN->value)->count(),
                'teachers' => User::role(UserRole::TEACHER->value)->count(),
                'parents'  => User::role(UserRole::PARENT->value)->count(),
                'no_roles' => User::doesntHave('roles')->count(),
            ]
        ]);
    }
}
