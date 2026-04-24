<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Database\Seeders\RoleSeeder;
use App\Enums\UserRole;
use App\Data\DateRangeData;
use App\Data\SlotData;
use App\Exceptions\SlotHasBookingsException;
use App\Services\SlotFactory;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Zap\Facades\Zap;

beforeEach(function () {
    (new RoleSeeder())->run();
});

it('creates teacher which doesnt have availability slots', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    expect($teacher->availabilitySchedules()->count())->toBe(0);

});

it('Creates availability slots for teacher', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $data = new SlotData(
        days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        start_date: Carbon::now(),
        end_date: Carbon::now()->addDays(16),
        start_time: '09:00',
        end_time: '21:00',
    );

    $slotFactoryService = new SlotFactory();
    $slotFactoryService->createAvailabilitySlots($data, $teacher);

    $dates = CarbonPeriod::create($data->start_date, $data->end_date)
        ->filter(fn (Carbon $date) => in_array(strtolower($date->englishDayOfWeek), $data->days));

    expect($teacher->availabilitySchedules()->count())->toBe(count($dates));

});

it('deletes one availability slot leaving 9 remaining', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    $data = new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy()->addDays(13),
        days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        start_time: '09:00',
        end_time: '21:00',
    );

    $service = new SlotFactory();
    $service->createAvailabilitySlots($data, $teacher);

    expect($teacher->availabilitySchedules()->count())->toBe(10);

    $service->deleteAvailabilitySlots(
        new DateRangeData(
            start_date: $monday->copy(),
            end_date: $monday->copy(),
        ),
        $teacher
    );

    expect($teacher->availabilitySchedules()->count())->toBe(9);

});

it('updates availability slot times for a date range', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    $data = new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy()->addDays(4),
        days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        start_time: '09:00',
        end_time: '21:00',
    );

    $service = new SlotFactory();
    $service->createAvailabilitySlots($data, $teacher);

    expect($teacher->availabilitySchedules()->count())->toBe(5);

    $updatedData = new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy()->addDays(4),
        days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        start_time: '10:00',
        end_time: '18:00',
    );

    $service->updateAvailabilitySlots($updatedData, $teacher);

    expect($teacher->availabilitySchedules()->count())->toBe(5);

    $period = $teacher->availabilitySchedules()->with('periods')->first()->periods->first();
    expect($period->start_time)->toStartWith('10:00');
    expect($period->end_time)->toStartWith('18:00');

});

it('blocks update when booked appointments exist in the date range', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    $data = new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy()->addDays(4),
        days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        start_time: '09:00',
        end_time: '21:00',
    );

    $service = new SlotFactory();
    $service->createAvailabilitySlots($data, $teacher);

    Zap::for($teacher)
        ->named('Parent Evening')
        ->appointment()
        ->from($monday->copy()->toDateString())
        ->addPeriod('10:00', '10:10')
        ->save();

    expect(fn () => $service->updateAvailabilitySlots($data, $teacher))
        ->toThrow(SlotHasBookingsException::class);

});

it('blocks delete when booked appointments exist in the date range', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    $data = new SlotData(
        start_date: $monday->copy(),
        end_date: $monday->copy()->addDays(4),
        days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        start_time: '09:00',
        end_time: '21:00',
    );

    $service = new SlotFactory();
    $service->createAvailabilitySlots($data, $teacher);

    Zap::for($teacher)
        ->named('Parent Evening')
        ->appointment()
        ->from($monday->copy()->toDateString())
        ->addPeriod('10:00', '10:10')
        ->save();

    expect(fn () => $service->deleteAvailabilitySlots(new DateRangeData(
        start_date: $monday->copy(),
        end_date: $monday->copy()->addDays(4),
    ), $teacher))->toThrow(SlotHasBookingsException::class);

});

it('stores slot_duration in availability schedule metadata', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    $data = new SlotData(
        start_date:    $monday->copy(),
        end_date:      $monday->copy(),
        days:          ['monday'],
        start_time:    '09:00',
        end_time:      '17:00',
        slot_duration: 15,
    );

    $service = new SlotFactory();
    $service->createAvailabilitySlots($data, $teacher);

    $schedule = $teacher->availabilitySchedules()->first();
    expect($schedule->metadata['slot_duration'])->toBe(15);

});

it('updates slot_duration in metadata when availability is updated', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $monday = Carbon::now()->next(Carbon::MONDAY);

    $data = new SlotData(
        start_date:    $monday->copy(),
        end_date:      $monday->copy(),
        days:          ['monday'],
        start_time:    '09:00',
        end_time:      '17:00',
        slot_duration: 10,
    );

    $service = new SlotFactory();
    $service->createAvailabilitySlots($data, $teacher);

    expect($teacher->availabilitySchedules()->first()->metadata['slot_duration'])->toBe(10);

    $updated = new SlotData(
        start_date:    $monday->copy(),
        end_date:      $monday->copy(),
        days:          ['monday'],
        start_time:    '09:00',
        end_time:      '17:00',
        slot_duration: 20,
    );

    $service->updateAvailabilitySlots($updated, $teacher);

    expect($teacher->availabilitySchedules()->first()->metadata['slot_duration'])->toBe(20);

});

it('Creates more availability slots for teacher on already existing ones', function () {

    $teacher = User::factory()->create();
    $teacher->assignRole(UserRole::TEACHER->value);

    $dataSetOne = new SlotData(
        days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        start_date: Carbon::now(),
        end_date: Carbon::now()->addDays(6),
        start_time: '09:00',
        end_time: '21:00',
    );

    $dataSetTwo = new SlotData(
        days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        start_date: Carbon::now(),
        end_date: Carbon::now()->addDays(16),
        start_time: '09:00',
        end_time: '21:00',
    );

    //SET ONE
    $slotFactoryService = new SlotFactory();
    $slotFactoryService->createAvailabilitySlots($dataSetOne, $teacher);

    $datesFromSetOne = CarbonPeriod::create($dataSetOne->start_date, $dataSetOne->end_date)
        ->filter(fn (Carbon $date) => in_array(strtolower($date->englishDayOfWeek), $dataSetOne->days));

    expect($teacher->availabilitySchedules()->count())->toBe(count($datesFromSetOne));

    //SET TWO
    $slotFactoryService->createAvailabilitySlots($dataSetTwo, $teacher);

    $datesFromSetTwo = CarbonPeriod::create($dataSetTwo->start_date, $dataSetTwo->end_date)
        ->filter(fn (Carbon $date) => in_array(strtolower($date->englishDayOfWeek), $dataSetTwo->days));

    expect($teacher->availabilitySchedules()->count())->toBe(count($datesFromSetTwo));

});
