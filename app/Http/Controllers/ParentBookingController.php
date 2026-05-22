<?php

namespace App\Http\Controllers;

use App\Data\AppointmentData;
use App\Exceptions\DuplicateAppointmentException;
use App\Models\User;
use App\Services\AppointmentService;
use App\Services\ParentBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ParentBookingController extends Controller
{
    public function index(Request $request): Response
    {
        $offset = (int) $request->query('week', 0);
        $monday = Carbon::today()->startOfWeek(Carbon::MONDAY)->addWeeks($offset);
        $sunday = $monday->copy()->addDays(6);
        $dates  = collect(range(0, 6))->map(fn ($i) => $monday->copy()->addDays($i));

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
            'rows' => (new ParentBookingService())->getWeekRows($request->user()->id, $monday, $sunday)->values(),
        ]);
    }

    public function store(Request $request, User $teacher, Carbon $date): RedirectResponse
    {
        $request->validate([
            'start_time' => ['required', 'string'],
        ]);

        if ($date->lt(Carbon::today())) {
            return back()->withErrors(['booking' => 'Cannot book past dates.']);
        }

        $parent   = $request->user();
        $dateStr  = $date->toDateString();

        $availability = $teacher->schedules()->availability()->forDate($dateStr)->first();
        $duration     = $availability?->metadata['slot_duration'] ?? 10;
        $startTime    = Carbon::parse($request->start_time)->format('H:i');
        $endTime      = Carbon::parse($request->start_time)->addMinutes($duration)->format('H:i');

        try {
            (new AppointmentService())->create(
                new AppointmentData(
                    date:       $date,
                    start_time: $startTime,
                    end_time:   $endTime,
                ),
                $teacher,
                $parent
            );
        } catch (DuplicateAppointmentException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back();
    }

    public function destroy(Request $request, User $teacher, Carbon $date): RedirectResponse
    {
        (new AppointmentService())->delete($date, $teacher, $request->user());

        return back();
    }
}
