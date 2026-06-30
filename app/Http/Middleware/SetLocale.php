<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($locale = $request->route('locale')) {
            session(['locale' => $locale]);
        }

        app()->setLocale(session('locale', 'en'));

        return $next($request);
    }
}
