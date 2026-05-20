<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Zap\Models\Schedule;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Route::bind('date',         fn ($value) => Carbon::createFromTimestampUTC($value));
        Route::bind('availability', fn ($value) => Schedule::findOrFail($value));
    }
}
