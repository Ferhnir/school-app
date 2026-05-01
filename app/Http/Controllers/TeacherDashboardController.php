<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $teacher = $request->user();
        $today   = Carbon::today();
        $end     = $today->copy()->addDays(13);

        $schedule = $teacher->schedules()
            ->availability()
            ->forDateRange($today->toDateString(), $end->toDateString())
            ->with('periods')
            ->orderBy('start_date')
            ->get()
            ->map(fn ($s) => [
                'id'            => $s->id,
                'date'          => $s->start_date->toDateString(),
                'label'         => $s->start_date->format('D, d M'),
                'start_time'    => Carbon::parse($s->periods->first()?->start_time)->format('H:i'),
                'end_time'      => Carbon::parse($s->periods->first()?->end_time)->format('H:i'),
                'slot_duration' => $s->metadata['slot_duration'] ?? 10,
            ]);

        return Inertia::render('TeacherDashboard', [
            'schedule' => $schedule,
        ]);
    }
}
