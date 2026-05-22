<?php

namespace App\Services;

use App\Models\User;
use App\Data\DateRangeData;
use App\Data\SlotData;
use App\Exceptions\SlotHasBookingsException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Zap\Facades\Zap;
use Throwable;
use Exception;

class AvailabilitySlots
{
    /**
     * @throws Throwable
     */
    public function create(SlotData $data, User $teacher): void
    {
        try {
            DB::beginTransaction();

            $dates = CarbonPeriod::create($data->start_date, $data->end_date)
                ->filter(fn (Carbon $date) => in_array(strtolower($date->englishDayOfWeek), $data->days));

            foreach ($dates as $date) {
                if ($teacher->availabilitySchedules()->whereDate('start_date', $date)->exists()) {
                    continue;
                }

                Zap::for($teacher)
                    ->named('parent-evening-hours')
                    ->availability()
                    ->from($date)
                    ->to($date->copy()->addDay())
                    ->addPeriod($data->start_time, $data->end_time)
                    ->withMetadata(['slot_duration' => $data->slot_duration])
                    ->save();
            }

            DB::commit();
        } catch (Exception|Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(SlotData $data, User $teacher): void
    {
        $this->guardAgainstBookedSlots($data->start_date, $data->end_date, $teacher);

        try {
            DB::beginTransaction();

            $teacher->schedules()
                ->availability()
                ->forDateRange($data->start_date->toDateString(), $data->end_date->toDateString())
                ->delete();

            $dates = CarbonPeriod::create($data->start_date, $data->end_date)
                ->filter(fn (Carbon $date) => in_array(strtolower($date->englishDayOfWeek), $data->days));

            foreach ($dates as $date) {
                Zap::for($teacher)
                    ->named('parent-evening-hours')
                    ->availability()
                    ->from($date)
                    ->to($date->copy()->addDay())
                    ->addPeriod($data->start_time, $data->end_time)
                    ->withMetadata(['slot_duration' => $data->slot_duration])
                    ->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(DateRangeData $data, User $teacher): void
    {
        $this->guardAgainstBookedSlots($data->start_date, $data->end_date, $teacher);

        $teacher->schedules()
            ->availability()
            ->forDateRange($data->start_date->toDateString(), $data->end_date->toDateString())
            ->delete();
    }

    private function guardAgainstBookedSlots(Carbon $from, Carbon $to, User $teacher): void
    {
        $hasBookings = $teacher->schedules()
            ->appointments()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->exists();

        if ($hasBookings) {
            throw new SlotHasBookingsException();
        }
    }
}
