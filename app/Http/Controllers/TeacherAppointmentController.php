<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AppointmentService;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Zap\Models\Schedule;

class TeacherAppointmentController extends Controller
{
    public function index(Request $request, User $teacher): Response
    {
        $this->authorizeAccess($request, $teacher);

        $today = Carbon::today();

        $appointments = $teacher->schedules()
            ->appointments()
            ->forDateRange($today->toDateString(), $today->copy()->addDays(30)->toDateString())
            ->with('periods')
            ->orderBy('start_date')
            ->get()
            ->map(function (Schedule $s) {
                $parentId = $s->metadata['parent_id'] ?? null;
                $parent   = $parentId ? User::find($parentId) : null;

                return [
                    'id'         => $s->id,
                    'date'       => $s->start_date->toDateString(),
                    'label'      => $s->start_date->format('D, d M'),
                    'start_time' => Carbon::parse($s->periods->first()?->start_time)->format('H:i'),
                    'end_time'   => Carbon::parse($s->periods->first()?->end_time)->format('H:i'),
                    'parent'     => $parent?->name,
                ];
            });

        return Inertia::render('TeacherAppointments', [
            'teacher'      => $teacher->only('id', 'name'),
            'appointments' => $appointments,
        ]);
    }

    public function destroy(Request $request, User $teacher, Schedule $appointment): RedirectResponse
    {
        $this->authorizeAccess($request, $teacher);

        abort_if($appointment->schedulable_id !== $teacher->id, 403);

        $parentId = $appointment->metadata['parent_id'] ?? null;
        $parent   = $parentId ? User::find($parentId) : null;

        (new AppointmentService())->deleteAppointment(
            $appointment->start_date,
            $teacher,
            $parent
        );

        return back();
    }

    private function authorizeAccess(Request $request, User $teacher): void
    {
        if ($request->user()->hasRole(UserRole::ADMIN->value)) {
            return;
        }

        abort_if($request->user()->id !== $teacher->id, 403);
    }
}
