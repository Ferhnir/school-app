<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $counts = DB::table('users')
            ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('roles.name as role', DB::raw('COUNT(users.id) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'role');


        return Inertia::render('UsersPanel', [
            'users_counts' => [
                'admins'   => $counts['admin'] ?? 0,
                'teachers' => $counts['teacher'] ?? 0,
                'parents'  => $counts['parent'] ?? 0,
                'no_roles' => $counts[null] ?? 0,
            ]
        ]);
    }
}
