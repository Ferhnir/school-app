<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ParentBookingsController extends Controller
{
    public function index(Request $request): Response
    {
        $parentId = $request->user()->id;

        $offset    = (int) $request->query('week', 0);
        $monday    = Carbon::today()->startOfWeek(Carbon::MONDAY)->addWeeks($offset);
        $sunday    = $monday->copy()->addDays(6);
        $startDate = $monday->toDateString();
        $endDate   = $sunday->toDateString();

        $dates    = collect(range(0, 6))->map(fn ($i) => $monday->copy()->addDays($i));
        $teachers = User::role(UserRole::TEACHER->value)->orderBy('name')->get();

        $rows = $teachers->map(function (User $teacher) use ($dates, $startDate, $endDate, $parentId) {
            $availabilities = $teacher->schedules()
                ->availability()
                ->whereBetween('start_date', [$startDate, $endDate])
                ->with('periods')
                ->get()
                ->keyBy(fn ($s) => $s->start_date->toDateString());

            $appointments = $teacher->schedules()
                ->appointments()
                ->whereBetween('start_date', [$startDate, $endDate])
                ->with('periods')
                ->get();

            $byDate = $appointments->groupBy(fn ($s) => $s->start_date->toDateString());

            $parentAppointments = $appointments
                ->filter(fn ($s) => ($s->metadata['parent_id'] ?? null) == $parentId)
                ->keyBy(fn ($s) => $s->start_date->toDateString());

            $days = $dates->mapWithKeys(function (Carbon $d) use ($availabilities, $byDate, $parentAppointments) {
                $key   = $d->toDateString();
                $avail = $availabilities->get($key);

                if (! $avail) {
                    return [$key => null];
                }

                if ($parentAppointments->has($key)) {
                    $period = $parentAppointments->get($key)->periods->first();
                    $time   = $period
                        ? Carbon::parse($period->start_time)->format('H:i') . ' – ' . Carbon::parse($period->end_time)->format('H:i')
                        : null;
                    return [$key => ['status' => 'booked', 'time' => $time]];
                }

                $duration   = $avail->metadata['slot_duration'] ?? 10;
                $period     = $avail->periods->first();
                $totalSlots = $period
                    ? (int) floor(Carbon::parse($period->start_time)->diffInMinutes(Carbon::parse($period->end_time)) / $duration)
                    : 0;
                $booked = ($byDate->get($key) ?? collect())->count();

                return [$key => ($totalSlots > 0 && $booked >= $totalSlots) ? 'fully_booked' : 'available'];
            });

            return [
                'teacher' => $teacher->only('id', 'name'),
                'days'    => $days,
            ];
        });

        return Inertia::render('ParentBookings', [
            'week' => [
                'prev'  => $offset - 1,
                'next'  => $offset + 1,
                'label' => $monday->format('d M') . ' – ' . $sunday->format('d M Y'),
            ],
            'dates' => $dates->map(fn (Carbon $d) => [
                'date'    => $d->toDateString(),
                'label'   => $d->format('D, d M'),
                'isToday' => $d->isToday(),
                'isPast'  => $d->lt(Carbon::today()),
            ])->values(),
            'rows' => $rows->values(),
        ]);
    }
}
