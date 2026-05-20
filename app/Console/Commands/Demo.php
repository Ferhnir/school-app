<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Database\Seeders\RoleSeeder;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Eloquent\Collection;
use Zap\Facades\Zap;
use App\Data\SlotData;
use App\Services\SlotFactory;

#[Signature('app:demo')]
#[Description('Seed demo teachers, parents, availability and bookings')]
class Demo extends Command
{
    private const int TEACHER_COUNT     = 5;
    private const int PARENT_COUNT      = 30;
    private const array SLOT_DURATIONS  = [5, 10, 15, 20];

    private Collection $teachers;

    public function __construct()
    {
        parent::__construct();
        $this->teachers = new Collection();
    }

    public function handle(): void
    {
        $this->seedRoles();
        $this->seedUsers();
        $this->createTeachersBookingSlots();
        $this->createBookingsWithTeachers();

        $this->info('Done.');
    }

    private function seedRoles(): void
    {
        if ($this->allRolesExist()) {
            $this->info('Roles already exist, skipping...');
            return;
        }

        (new RoleSeeder())->run();
        $this->info('Roles created.');
    }

    private function seedUsers(): void
    {
        (new UserSeeder())->run();

        if (User::role(UserRole::TEACHER->value)->exists() && User::role(UserRole::PARENT->value)->exists()) {
            $this->teachers = User::role(UserRole::TEACHER->value)->get();
            $this->info('Users already exist, skipping...');
            return;
        }

        $this->teachers = User::factory()->count(self::TEACHER_COUNT)->create();
        $this->teachers->each(fn (User $u) => $u->assignRole(UserRole::TEACHER->value));

        User::factory()->count(self::PARENT_COUNT)->create()
            ->each(fn (User $u) => $u->assignRole(UserRole::PARENT->value));

        $this->info(self::TEACHER_COUNT . ' teachers and ' . self::PARENT_COUNT . ' parents created.');
    }

    private function createTeachersBookingSlots(): void
    {
        $slotFactory = new SlotFactory();

        $this->teachers->each(function (User $teacher) use ($slotFactory) {
            $duration = self::SLOT_DURATIONS[array_rand(self::SLOT_DURATIONS)];

            $data = new SlotData(
                start_date:    Carbon::now(),
                end_date:      Carbon::now()->addDays(13),
                days:          ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                start_time:    '09:00',
                end_time:      '21:00',
                slot_duration: $duration,
            );

            $slotFactory->createAvailabilitySlots($data, $teacher);
            $this->info("Teacher {$teacher->id}: {$duration}-min slots created.");
        });
    }

    private function createBookingsWithTeachers(): void
    {
        $parents = User::role(UserRole::PARENT->value)->get();

        $weekdays = collect(
            CarbonPeriod::create(Carbon::now(), Carbon::now()->addDays(13))
                ->filter(fn (Carbon $date) => $date->isWeekday())
                ->toArray()
        );

        $this->teachers->each(function (User $teacher) use ($parents, $weekdays) {
            $bookingCount  = rand(1, min(10, $weekdays->count()));
            $selectedDates = $weekdays->shuffle()->take($bookingCount);

            foreach ($selectedDates as $date) {
                $availability = $teacher->schedules()
                    ->availability()
                    ->forDate($date->toDateString())
                    ->first();

                $duration = $availability?->metadata['slot_duration'] ?? 10;

                $slots = collect($teacher->getBookableSlots($date->toDateString(), $duration, 0))
                    ->where('is_available', true);

                if ($slots->isEmpty()) {
                    continue;
                }

                $slot = $slots->random();

                Zap::for($teacher)
                    ->named('Parent Evening')
                    ->appointment()
                    ->from($date->toDateString())
                    ->addPeriod($slot['start_time'], $slot['end_time'])
                    ->withMetadata([
                        'parent_id' => $parents->random()->id,
                        'type'      => 'meeting-with-parent',
                    ])
                    ->save();
            }

            $this->info("Teacher {$teacher->id}: {$bookingCount} booking(s) created.");
        });
    }

    private function allRolesExist(): bool
    {
        $required = collect(UserRole::cases())->map(fn ($r) => $r->value);
        $existing = Role::whereIn('name', $required)->pluck('name');

        return $required->diff($existing)->isEmpty();
    }
}
