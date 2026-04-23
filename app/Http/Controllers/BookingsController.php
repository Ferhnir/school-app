<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingsController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        return Inertia::render('Bookings', [
            'teachers' => User::role(UserRole::TEACHER->value)->get()
        ]);
    }
}
