<?php

namespace App\Services;

use App\Data\AppointmentData;
use App\Exceptions\DuplicateAppointmentException;
use App\Models\User;
use Carbon\Carbon;
use Zap\Facades\Zap;
use Throwable;
use Exception;

class AppointmentService extends FactoryService
{
    public function create(AppointmentData $data, User $teacher, User $parent): void
    {
        $this->guardAgainstDuplicate($data->date, $teacher, $parent);

        Zap::for($teacher)
            ->named('Parent Evening')
            ->appointment()
            ->from($data->date->toDateString())
            ->addPeriod($data->start_time, $data->end_time)
            ->withMetadata(['parent_id' => $parent->id])
            ->save();
    }

    public function delete(Carbon $date, User $teacher, User $parent): void
    {
        $teacher->schedules()
            ->appointments()
            ->forDate($date->toDateString())
            ->whereJsonContains('metadata->parent_id', $parent->id)
            ->delete();
    }

    private function guardAgainstDuplicate(Carbon $date, User $teacher, User $parent): void
    {
        $hasAppointment = $teacher->schedules()
            ->appointments()
            ->forDate($date->toDateString())
            ->whereJsonContains('metadata->parent_id', $parent->id)
            ->exists();

        if ($hasAppointment) {
            throw new DuplicateAppointmentException();
        }
    }
}
