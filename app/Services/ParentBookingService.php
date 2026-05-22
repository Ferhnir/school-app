<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Zap\Models\Schedule;
use Throwable;
use Exception;

class ParentBookingService extends FactoryService
{
    public function getWeekRows(User $parent, Carbon $monday, Carbon $sunday): Collection
    {
        $startDate = $monday->toDateString();
        $endDate   = $sunday->toDateString();
        $dates     = collect(range(0, 6))->map(fn ($i) => $monday->copy()->addDays($i));

        $teachers   = User::query()->role(UserRole::TEACHER->value)->orderBy('name')->get();
        $teacherIds = $teachers->pluck('id');

        $allAvailabilities = Schedule::query()
            ->where('schedulable_type', User::class)
            ->whereIn('schedulable_id', $teacherIds)
            ->availability()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with('periods')
            ->get()
            ->groupBy('schedulable_id');

        $allAppointments = Schedule::query()
            ->where('schedulable_type', User::class)
            ->whereIn('schedulable_id', $teacherIds)
            ->appointments()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with('periods')
            ->get()
            ->groupBy('schedulable_id');

        return $teachers->map(function (User $teacher) use ($dates, $allAvailabilities, $allAppointments, $parent) {
            $availabilities = ($allAvailabilities->get($teacher->id) ?? collect())
                ->keyBy(fn ($s) => $s->start_date->toDateString());

            $appointments = $allAppointments->get($teacher->id) ?? collect();
            $byDate       = $appointments->groupBy(fn ($s) => $s->start_date->toDateString());

            $parentAppointments = $appointments
                ->filter(fn ($s) => ($s->metadata['parent_id'] ?? null) == $parent)
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
    }
}
