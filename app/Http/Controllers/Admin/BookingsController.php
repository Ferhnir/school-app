<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BookingsController extends Controller
{
    public function index(Request $request): Response
    {
        $offset    = (int) $request->query('week', 0);
        $monday    = Carbon::now()->startOfWeek(Carbon::MONDAY)->addWeeks($offset);
        $sunday    = $monday->copy()->addDays(6);
        $weekDates = collect(range(0, 6))->map(fn ($i) => $monday->copy()->addDays($i));

        $teachers = User::role(UserRole::TEACHER->value)
            ->with(['schedules' => function ($query) use ($monday, $sunday) {
                $query->forDateRange($monday->toDateString(), $sunday->toDateString())
                    ->with('periods');
            }])
            ->get()
            ->map(function (User $teacher) use ($weekDates) {
                $days = $weekDates->map(function (Carbon $date) use ($teacher) {
                    $dateStr = $date->toDateString();

                    $availability = $teacher->schedules
                        ->filter(fn ($s) => $s->isAvailability() && $s->start_date->toDateString() === $dateStr)
                        ->first();

                    $appointments = $teacher->schedules
                        ->filter(fn ($s) => $s->isAppointment() && $s->start_date->toDateString() === $dateStr);

                    $availableMinutes = 0;
                    if ($availability) {
                        foreach ($availability->periods as $period) {
                            $availableMinutes += Carbon::parse($period->start_time)
                                ->diffInMinutes(Carbon::parse($period->end_time));
                        }
                    }

                    $bookedMinutes = 0;
                    foreach ($appointments as $appt) {
                        foreach ($appt->periods as $period) {
                            $bookedMinutes += Carbon::parse($period->start_time)
                                ->diffInMinutes(Carbon::parse($period->end_time));
                        }
                    }

                    $status = match (true) {
                        $availableMinutes === 0             => 'unavailable',
                        $bookedMinutes === 0                => 'free',
                        $bookedMinutes >= $availableMinutes => 'full',
                        default                             => 'partial',
                    };

                    return [
                        'date'              => $dateStr,
                        'label'             => $date->format('D d/m'),
                        'bookings_count'    => $appointments->count(),
                        'available_minutes' => $availableMinutes,
                        'booked_minutes'    => $bookedMinutes,
                        'status'            => $status,
                    ];
                })->values();

                return [
                    'id'   => $teacher->id,
                    'name' => $teacher->name,
                    'days' => $days,
                ];
            });

        return Inertia::render('Bookings', [
            'week' => [
                'offset' => $offset,
                'prev'   => $offset - 1,
                'next'   => $offset + 1,
                'start'  => $monday->toDateString(),
                'end'    => $sunday->toDateString(),
                'label'  => $monday->format('d M') . ' – ' . $sunday->format('d M Y'),
            ],
            'teachers' => $teachers,
            'parents'  => User::role(UserRole::PARENT->value)->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

}
