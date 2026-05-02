<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BookingsController;
use App\Http\Controllers\Admin\UserController;
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

        Route::get('/parent/dashboard', [ParentDashboardController::class, 'index'])
            ->middleware('role:' . UserRole::PARENT->value)
            ->name('parent.dashboard');

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


    Route::get('/teachers/{teacher}/date/{date}',  [TeacherSlotsController::class, 'index'])
        ->name('slots.index');
    Route::post('/teachers/{teacher}/date/{date}', [TeacherSlotsController::class, 'store'])
        ->name('slots.store');

    Route::get('/teachers/{teacher}/appointments',                  [TeacherAppointmentController::class, 'index'])->name('teachers.appointments.index');
    Route::delete('/teachers/{teacher}/appointments/{appointment}', [TeacherAppointmentController::class, 'destroy'])->name('teachers.appointments.destroy');
});



Route::get('/lang/{locale}', function ($locale) {
    app()->setLocale($locale);
    return redirect()->back();
})->name('language.switch');

require __DIR__.'/auth.php';
