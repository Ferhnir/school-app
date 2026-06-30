<?php

namespace App\Services;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class TeacherDayBookingsPdf
{
    public function rowsFor(User $teacher, Carbon $date): Collection
    {
        $appointments = $teacher->schedules()
            ->appointments()
            ->forDate($date->toDateString())
            ->with('periods')
            ->get();

        $parentIds = $appointments->pluck('metadata.parent_id')->filter()->unique();
        $parents   = User::whereIn('id', $parentIds)->get()->keyBy('id');

        return $appointments
            ->map(function ($appt) use ($parents) {
                $parentId = $appt->metadata['parent_id'] ?? null;
                $parent   = $parentId ? $parents->get($parentId) : null;
                $time     = Carbon::parse($appt->periods->first()?->start_time)->format('H:i');

                return [$parent?->name ?? '—', $parent?->email ?? '—', $time];
            })
            ->sortBy(fn ($row) => $row[2])
            ->values();
    }

    public function download(User $teacher, Carbon $date): Response
    {
        $pdf = Pdf::loadView('pdf.bookings', [
            'teacher' => $teacher,
            'date'    => $date,
            'rows'    => $this->rowsFor($teacher, $date),
        ])->setPaper('a4');

        return $pdf->download('bookings-' . $date->toDateString() . '.pdf');
    }
}
