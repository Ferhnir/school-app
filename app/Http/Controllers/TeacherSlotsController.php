<?php

namespace App\Http\Controllers;

use App\Data\AppointmentData;
use App\Exceptions\DuplicateAppointmentException;
use App\Http\Requests\StoreBookingRequest;
use App\Models\User;
use App\Services\AppointmentService;
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

        $appointments = $teacher->schedules()
            ->appointments()
            ->forDate($dateStr)
            ->with('periods')
            ->get();

        $parentIds = $appointments->pluck('metadata.parent_id')->filter()->unique();
        $parents   = User::whereIn('id', $parentIds)->pluck('name', 'id');

        $bookedMap = [];
        foreach ($appointments as $appt) {
            $parentId = $appt->metadata['parent_id'] ?? null;
            foreach ($appt->periods as $period) {
                $key             = Carbon::parse($period->start_time)->format('H:i');
                $bookedMap[$key] = $parents[$parentId] ?? null;
            }
        }

        $isToday = $dateStr === Carbon::today()->toDateString();
        $now     = Carbon::now();

        $slots = collect($teacher->getBookableSlots($dateStr, $duration, 0))
            ->map(function ($slot) use ($bookedMap) {
                $key               = Carbon::parse($slot['start_time'])->format('H:i');
                $slot['booked_by'] = $bookedMap[$key] ?? null;
                return $slot;
            })
            ->filter(function ($slot) use ($isToday, $now) {
                if (!$slot['is_available']) return true;
                if ($isToday && Carbon::parse($slot['start_time'])->lte($now)) return false;
                return true;
            })
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
            (new AppointmentService())->create(
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
