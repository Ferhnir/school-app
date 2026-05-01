<?php

namespace App\Services;

use App\Data\DateRangeData;
use App\Data\SlotData;
use App\Exceptions\SlotHasBookingsException;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Zap\Models\Schedule;

class AvailabilityService
{
    public function __construct(private SlotFactory $slots) {}

    public function getUpcoming(User $teacher, int $days = 14): Collection
    {
        $today = Carbon::today();
        $end   = $today->copy()->addDays($days - 1)->toDateString();

        $bookingCounts = $teacher->schedules()
            ->appointments()
            ->whereBetween('start_date', [$today->toDateString(), $end])
            ->get()
            ->groupBy(fn ($s) => $s->start_date->toDateString())
            ->map->count();

        return $teacher->schedules()
            ->availability()
            ->whereBetween('start_date', [$today->toDateString(), $end])
            ->with('periods')
            ->orderBy('start_date')
            ->get()
            ->map(fn ($s) => [
                'id'             => $s->id,
                'date'           => $s->start_date->toDateString(),
                'label'          => $s->start_date->format('D, d M'),
                'start_time'     => Carbon::parse($s->periods->first()?->start_time)->format('H:i'),
                'end_time'       => Carbon::parse($s->periods->first()?->end_time)->format('H:i'),
                'slot_duration'  => $s->metadata['slot_duration'] ?? 10,
                'bookings_count' => $bookingCounts[$s->start_date->toDateString()] ?? 0,
            ]);
    }

    public function create(User $teacher, Carbon $startDate, Carbon $endDate, array $days, string $startTime, string $endTime, int $slotDuration): void
    {
        $this->slots->createAvailabilitySlots(
            new SlotData(
                start_date:    $startDate,
                end_date:      $endDate,
                days:          $days,
                start_time:    $startTime,
                end_time:      $endTime,
                slot_duration: $slotDuration,
            ),
            $teacher
        );
    }

    /**
     * @throws SlotHasBookingsException
     */
    public function update(User $teacher, Schedule $availability, string $startTime, string $endTime, int $slotDuration): void
    {
        $this->slots->updateAvailabilitySlots(
            new SlotData(
                start_date:    $availability->start_date,
                end_date:      $availability->start_date,
                days:          [strtolower($availability->start_date->englishDayOfWeek)],
                start_time:    $startTime,
                end_time:      $endTime,
                slot_duration: $slotDuration,
            ),
            $teacher
        );
    }

    /**
     * @throws SlotHasBookingsException
     */
    public function delete(User $teacher, Schedule $availability): void
    {
        $this->slots->deleteAvailabilitySlots(
            new DateRangeData(
                start_date: $availability->start_date,
                end_date:   $availability->start_date,
            ),
            $teacher
        );
    }
}
