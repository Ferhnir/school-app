<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function redirect(): RedirectResponse|Redirect
    {
        $user = Auth::user();

        $route =  match (true) {
            $user->hasRole(UserRole::ADMIN->value) => 'admin.dashboard',
            $user->hasRole(UserRole::TEACHER->value) =>'teacher.dashboard',
            $user->hasRole(UserRole::PARENT->value) => 'parent.dashboard',
            default => false,
        };

        if (! $route) abort(code: Response::HTTP_FORBIDDEN);

        return redirect()->route($route, [session()->get('locale') ?? 'en']);
    }
}
