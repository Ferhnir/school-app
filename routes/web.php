<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BookingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ParentBookingController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\TeacherAppointmentController;
use App\Http\Controllers\TeacherAvailabilityController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TeacherSlotsController;

use Illuminate\Support\Facades\Route;
use App\Enums\UserRole;

Route::get('/', [DashboardController::class, 'redirect'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::group([
    'prefix' => '{locale}',
    'where' => ['locale' => 'en|pl|ua'],
], function (){
    Route::middleware(['auth', 'verified'])->group(function (){

        //DASHBOARD
        Route::get('/', [DashboardController::class, 'redirect'])->name('dashboard');

        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('role:' . UserRole::ADMIN->value)
            ->name('admin.dashboard');

        Route::middleware('role:' . UserRole::PARENT->value)->group(function () {
            Route::get('/parent/dashboard', [ParentDashboardController::class, 'index'])
                ->name('parent.dashboard');
            Route::get('/parent/dashboard/download', [ParentDashboardController::class, 'download'])
                ->name('parent.dashboard.download');
            Route::post('/parent/dashboard/email', [ParentDashboardController::class, 'email'])
                ->name('parent.dashboard.email');
        });

        Route::get('/parent/bookings', [ParentBookingController::class, 'index'])
            ->middleware('role:' . UserRole::PARENT->value)
            ->name('parent.bookings.index');

        Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'index'])
            ->middleware(UserRole::middleware(UserRole::TEACHER))
            ->name('teacher.dashboard');

        //AUTH USER Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::middleware('role:' . UserRole::ADMIN->value)->group(function () {
            Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
            Route::get('/bookings',    [BookingsController::class, 'index'])->name('admin.bookings.index');
        });

    });
});

// ADMIN — mutations with route-model binding live outside {locale}
Route::middleware([
    'auth',
    'verified',
    'role:' . UserRole::ADMIN->value,
])->group(function () {
    Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::patch('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::patch('/admin/users/{user}/suspend', [UserController::class, 'suspend'])->name('admin.users.suspend');
});

Route::middleware('role:' . UserRole::PARENT->value)->group(function () {
    Route::get('/parent/calendar/{date}/download', [ParentDashboardController::class, 'downloadDate'])
        ->name('parent.calendar.download');
    Route::post('/parent/calendar/{date}/email', [ParentDashboardController::class, 'emailDate'])
        ->name('parent.calendar.email');
});

//PARENTS
Route::middleware([
    'auth',
    'verified',
    'role:' . UserRole::PARENT->value,
])->group(function () {
    Route::get('/teachers/{teacher}/date/{date}/slots', [TeacherSlotsController::class, 'index'])
        ->name('parent.slots.index');
    Route::post('/teachers/{teacher}/date/{date}/book', [ParentBookingController::class, 'store'])
        ->name('parent.slots.store');
    Route::delete('/teachers/{teacher}/date/{date}/book', [ParentBookingController::class, 'destroy'])
        ->name('parent.slots.destroy');
});

//TEACHERS
Route::middleware([
    'auth',
    'verified',
    UserRole::middleware(UserRole::TEACHER, UserRole::ADMIN)
])->group(function () {
    //AVAILABILITY MANAGEMENT
    Route::resource('teachers.availabilities', TeacherAvailabilityController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('/teachers/{teacher}/availabilities/{availability}/download', [TeacherAvailabilityController::class, 'download'])
        ->name('teachers.availabilities.download');

    Route::controller(TeacherSlotsController::class)->group(function () {
        Route::get('/teachers/{teacher}/date/{date}', 'index')->name('slots.index');
        Route::post('/teachers/{teacher}/date/{date}', 'store')->name('slots.store');
    });

    Route::controller(TeacherAppointmentController::class)->group(function () {
        Route::get('/teachers/{teacher}/appointments', 'index')->name('teachers.appointments.index');
        Route::delete('/teachers/{teacher}/appointments/{appointment}', 'destroy')
            ->name('teachers.appointments.destroy');
    });
});

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'pl', 'ua'], true)) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }

    return redirect()->back();
})->name('language.switch');

require __DIR__.'/auth.php';
