<?php

namespace App\Http\Controllers;

use App\Data\AppointmentData;
use App\Exceptions\DuplicateAppointmentException;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ParentBookingController extends Controller
{
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
            (new AppointmentService())->createAppointment(
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
        (new AppointmentService())->deleteAppointment($date, $teacher, $request->user());

        return back();
    }
}
