<?php

namespace App\Http\Controllers;

use App\Mail\TodayBookings;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Zap\Models\Schedule;

class ParentDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $monthParam = $request->query('month');
        $month = $monthParam
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : Carbon::today()->startOfMonth();

        return Inertia::render('Dashboard/Parent', [
            'bookings' => $this->bookingsForDate($request->user(), Carbon::today()),
            'today'    => Carbon::today()->format('l, d F Y'),
            'calendar' => $this->monthCalendar($request->user(), $month),
        ]);
    }

    public function download(Request $request): HttpResponse
    {
        $parent   = $request->user();
        $today    = Carbon::today();
        $bookings = $this->bookingsForDate($parent, $today);

        $pdf = Pdf::loadView('pdf.parent-today', [
            'parent'   => $parent,
            'date'     => $today,
            'bookings' => $bookings,
        ])->setPaper('a4');

        return $pdf->download('my-bookings-' . $today->toDateString() . '.pdf');
    }

    public function email(Request $request): RedirectResponse
    {
        $parent   = $request->user();
        $bookings = $this->bookingsForDate($parent, Carbon::today());

        Mail::to($parent->email)->send(new TodayBookings($parent, Carbon::today(), $bookings));

        return back()->with('message', 'Booking summary sent to ' . $parent->email);
    }

    public function downloadDate(Request $request, string $date): HttpResponse
    {
        $parent   = $request->user();
        $carbon   = Carbon::parse($date);
        $bookings = $this->bookingsForDate($parent, $carbon);

        $pdf = Pdf::loadView('pdf.parent-today', [
            'parent'   => $parent,
            'date'     => $carbon,
            'bookings' => $bookings,
        ])->setPaper('a4');

        return $pdf->download('my-bookings-' . $date . '.pdf');
    }

    public function emailDate(Request $request, string $date): RedirectResponse
    {
        $parent   = $request->user();
        $carbon   = Carbon::parse($date);
        $bookings = $this->bookingsForDate($parent, $carbon);

        Mail::to($parent->email)->send(new TodayBookings($parent, $carbon, $bookings));

        return back()->with('message', 'Booking summary sent to ' . $parent->email);
    }

    private function monthCalendar(User $parent, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        $appointments = Schedule::query()
            ->appointments()
            ->whereJsonContains('metadata->parent_id', $parent->id)
            ->whereDate('start_date', '>=', $start->toDateString())
            ->whereDate('start_date', '<=', $end->toDateString())
            ->with(['schedulable', 'periods'])
            ->get();

        $byDate = $appointments->groupBy(fn ($appt) => Carbon::parse($appt->start_date)->toDateString());

        $days    = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $dateStr  = $current->toDateString();
            $dayAppts = $byDate->get($dateStr, collect());

            $bookings = $dayAppts->map(function ($appt) {
                $period = $appt->periods->first();
                return [
                    'teacher' => $appt->schedulable->name,
                    'time'    => $period
                        ? Carbon::parse($period->start_time)->format('H:i') . ' – ' . Carbon::parse($period->end_time)->format('H:i')
                        : '—',
                ];
            })->sortBy('time')->values()->toArray();

            $days[] = [
                'date'     => $dateStr,
                'label'    => $current->format('j'),
                'count'    => count($bookings),
                'bookings' => $bookings,
                'is_today' => $current->isToday(),
            ];

            $current->addDay();
        }

        return [
            'label'         => $month->format('F Y'),
            'prev_month'    => $month->copy()->subMonth()->format('Y-m'),
            'next_month'    => $month->copy()->addMonth()->format('Y-m'),
            'days'          => $days,
            'start_weekday' => (int) $start->format('N') - 1, // 0=Mon … 6=Sun
        ];
    }

    private function bookingsForDate(User $parent, Carbon $date): Collection
    {
        return Schedule::query()
            ->appointments()
            ->forDate($date->toDateString())
            ->whereJsonContains('metadata->parent_id', $parent->id)
            ->with(['schedulable', 'periods'])
            ->get()
            ->map(function ($appt) {
                $period = $appt->periods->first();
                return [
                    'teacher' => $appt->schedulable->name,
                    'time'    => $period
                        ? Carbon::parse($period->start_time)->format('H:i') . ' – ' . Carbon::parse($period->end_time)->format('H:i')
                        : '—',
                ];
            })
            ->sortBy('time')
            ->values();
    }
}
