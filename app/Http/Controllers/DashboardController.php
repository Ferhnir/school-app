<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DashboardController extends Controller
{
    public function redirect(): RedirectResponse|Redirect
    {
        $user = Auth::user();

        $rolesArr = $user->roles->pluck('name')->toArray();

        $locale = session()->get('locale') ?? 'en';

        return match (true) {
            in_array(UserRole::ADMIN->value, $rolesArr) => redirect()->route('admin.dashboard', [$locale]),
            in_array(UserRole::TEACHER->value, $rolesArr) => redirect()->route('teacher.dashboard', [$locale]),
            in_array(UserRole::PARENT->value, $rolesArr) => redirect()->route('parent.dashboard', [$locale]),
            default => abort(403),
        };
    }
}
