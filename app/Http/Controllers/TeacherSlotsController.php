<?php

namespace App\Http\Controllers;

use App\Data\AppointmentData;
use App\Exceptions\DuplicateAppointmentException;
use App\Http\Requests\StoreBookingRequest;
use App\Models\User;
use App\Services\AppointmentFactory;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class TeacherSlotsController extends Controller
{
    public function index(User $teacher, Carbon $date): JsonResponse
    {
        $dateStr      = $date->toDateString();
        $availability = $teacher->schedules()
            ->availability()
            ->forDate($dateStr)
            ->first();

        $duration = $availability?->metadata['slot_duration'] ?? 10;

        $isToday = $dateStr === Carbon::today()->toDateString();

        $slots = collect($teacher->getBookableSlots($dateStr, $duration, 0))
            ->where('is_available', true)
            ->when($isToday, fn ($c) => $c->filter(
                fn ($slot) => Carbon::parse($slot['start_time'])->gt(Carbon::now())
            ))
            ->values();

        return response()->json($slots);
    }

    public function store(StoreBookingRequest $request, User $teacher, Carbon $date): RedirectResponse
    {
        $dateStr      = $date->toDateString();
        $availability = $teacher->schedules()->availability()->forDate($dateStr)->first();
        $duration     = $availability?->metadata['slot_duration'] ?? 10;
        $startTime    = Carbon::parse($request->start_time)->format('H:i');
        $endTime      = Carbon::parse($request->start_time)->addMinutes($duration)->format('H:i');

        try {
            (new AppointmentFactory())->createAppointment(
                new AppointmentData(
                    date:       $date,
                    start_time: $startTime,
                    end_time:   $endTime,
                ),
                $teacher,
                $request->parent
            );
        } catch (DuplicateAppointmentException $e) {
            return back()->withErrors(['booking' => $e->getMessage()]);
        }

        return back();
    }
}
