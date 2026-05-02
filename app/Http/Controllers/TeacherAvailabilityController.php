<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Exceptions\SlotHasBookingsException;
use App\Http\Requests\StoreTeacherAvailabilityRequest;
use App\Http\Requests\UpdateTeacherAvailabilityRequest;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Zap\Models\Schedule;

class TeacherAvailabilityController extends Controller
{
    public function __construct(private AvailabilityService $availability) {}

    public function index(Request $request, User $teacher): Response
    {
        $this->authorizeAccess($request, $teacher);

        return Inertia::render('TeacherDashboard', [
            'teacher'  => $teacher->only('id', 'name'),
            'schedule' => $this->availability->getUpcoming($teacher),
        ]);
    }

    public function store(StoreTeacherAvailabilityRequest $request, User $teacher): RedirectResponse
    {
        $this->authorizeAccess($request, $teacher);

        $this->availability->create(
            $teacher,
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date),
            $request->days,
            $request->start_time,
            $request->end_time,
            (int) $request->slot_duration,
        );

        return back();
    }

    public function update(UpdateTeacherAvailabilityRequest $request, User $teacher, Schedule $availability): RedirectResponse
    {
        $this->authorizeAccess($request, $teacher);
        $this->authorizeOwnership($availability, $teacher);

        try {
            $this->availability->update(
                $teacher,
                $availability,
                $request->start_time,
                $request->end_time,
                (int) $request->slot_duration,
            );
        } catch (SlotHasBookingsException) {
            return back()->withErrors(['schedule' => 'Cannot update: this date already has bookings.']);
        }

        return back();
    }

    public function download(Request $request, User $teacher, Schedule $availability): HttpResponse
    {
        $this->authorizeAccess($request, $teacher);
        $this->authorizeOwnership($availability, $teacher);

        $date = $availability->start_date;

        $appointments = $teacher->schedules()
            ->appointments()
            ->forDate($date->toDateString())
            ->with('periods')
            ->get();

        $parentIds = $appointments->pluck('metadata.parent_id')->filter()->unique();
        $parents   = User::whereIn('id', $parentIds)->get()->keyBy('id');

        $rows = $appointments
            ->map(function ($appt) use ($parents) {
                $parentId = $appt->metadata['parent_id'] ?? null;
                $parent   = $parentId ? $parents->get($parentId) : null;
                $time     = Carbon::parse($appt->periods->first()?->start_time)->format('H:i');

                return [$parent?->name ?? '—', $parent?->email ?? '—', $time];
            })
            ->sortBy(fn ($row) => $row[2])
            ->values();

        $pdf = Pdf::loadView('pdf.bookings', [
            'teacher' => $teacher,
            'date'    => $date,
            'rows'    => $rows,
        ])->setPaper('a4');

        return $pdf->download('bookings-' . $date->toDateString() . '.pdf');
    }

    public function destroy(Request $request, User $teacher, Schedule $availability): RedirectResponse
    {
        $this->authorizeAccess($request, $teacher);
        $this->authorizeOwnership($availability, $teacher);

        $this->availability->delete($teacher, $availability);

        return back();
    }

    private function authorizeAccess(Request $request, User $teacher): void
    {
        if ($request->user()->hasRole(UserRole::ADMIN->value)) {
            return;
        }

        abort_if($request->user()->id !== $teacher->id, 403);
    }

    private function authorizeOwnership(Schedule $availability, User $teacher): void
    {
        abort_if($availability->schedulable_id !== $teacher->id, 403);
    }
}
