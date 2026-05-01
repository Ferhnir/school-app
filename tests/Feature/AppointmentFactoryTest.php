<?php

use App\Data\AppointmentData;
use App\Data\SlotData;
use App\Exceptions\DuplicateAppointmentException;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\AppointmentService;
use App\Services\SlotFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    (new RoleSeeder())->run();
});

it('creates an appointment for a parent with a teacher', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $parent = User::factory()->create();
    $parent->assignRole(UserRole::PARENT->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    (new SlotFactory())->createAvailabilitySlots(new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy(),
        days: ['monday'],
        start_time: '09:00',
        end_time: '21:00',
    ), $teacher);

    $data = new AppointmentData(
        date: $monday->copy(),
        start_time: '10:00',
        end_time: '10:10',
    );

    (new AppointmentService())->createAppointment($data, $teacher, $parent);

    expect($teacher->appointmentSchedules()->count())->toBe(1);

});

it('blocks a second appointment for the same parent with the same teacher on the same day', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $parent = User::factory()->create();
    $parent->assignRole(UserRole::PARENT->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    (new SlotFactory())->createAvailabilitySlots(new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy(),
        days: ['monday'],
        start_time: '09:00',
        end_time: '21:00',
    ), $teacher);

    $factory = new AppointmentService();

    $factory->createAppointment(new AppointmentData(
        date: $monday->copy(),
        start_time: '10:00',
        end_time: '10:10',
    ), $teacher, $parent);

    expect(fn () => $factory->createAppointment(new AppointmentData(
        date: $monday->copy(),
        start_time: '11:00',
        end_time: '11:10',
    ), $teacher, $parent))->toThrow(DuplicateAppointmentException::class);

});

it('allows a different parent to book the same teacher on the same day', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $parentOne = User::factory()->create();
    $parentOne->assignRole(UserRole::PARENT->value);

    $parentTwo = User::factory()->create();
    $parentTwo->assignRole(UserRole::PARENT->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    (new SlotFactory())->createAvailabilitySlots(new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy(),
        days: ['monday'],
        start_time: '09:00',
        end_time: '21:00',
    ), $teacher);

    $factory = new AppointmentService();

    $factory->createAppointment(new AppointmentData(
        date: $monday->copy(),
        start_time: '10:00',
        end_time: '10:10',
    ), $teacher, $parentOne);

    $factory->createAppointment(new AppointmentData(
        date: $monday->copy(),
        start_time: '10:10',
        end_time: '10:20',
    ), $teacher, $parentTwo);

    expect($teacher->appointmentSchedules()->count())->toBe(2);

});

it('deletes an appointment for a parent', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $parent = User::factory()->create();
    $parent->assignRole(UserRole::PARENT->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    (new SlotFactory())->createAvailabilitySlots(new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy(),
        days: ['monday'],
        start_time: '09:00',
        end_time: '21:00',
    ), $teacher);

    $factory = new AppointmentService();

    $factory->createAppointment(new AppointmentData(
        date: $monday->copy(),
        start_time: '10:00',
        end_time: '10:10',
    ), $teacher, $parent);

    expect($teacher->appointmentSchedules()->count())->toBe(1);

    $factory->deleteAppointment($monday->copy(), $teacher, $parent);

    expect($teacher->appointmentSchedules()->count())->toBe(0);

});
