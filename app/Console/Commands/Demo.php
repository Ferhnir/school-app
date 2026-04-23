<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Database\Seeders\RoleSeeder;
use App\Enums\UserRole;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Eloquent\Collection;
use Zap\Facades\Zap;
use App\Data\SlotData;
use App\Services\SlotFactory;

#[Signature('app:demo')]
#[Description('Command description')]
class Demo extends Command
{
    private Collection $teachers;

    public function __construct()
    {
        parent::__construct();

        $this->teachers = new Collection();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //ROLES
        if (!$this->allRolesExist()) {
            $roleSeeder = new RoleSeeder();
            $roleSeeder->run();
            $this->info('Roles Created');
        } else {
            $this->info('All roles already exist, skipping...');
        }

        //USERS
        $adminSeeder = new UserSeeder();
        $adminSeeder->run();

        if ( !User::role(UserRole::TEACHER->value)->exists() || !User::role(UserRole::PARENT->value)->exists()) {
            $this->seedUsers();
            $this->info('Users Created');
        } else {
            $this->teachers = User::role(UserRole::TEACHER->value)->get();
            $this->info('Users with Teacher and Parent roles already exits, skipping');
        }

        //ADD BOOKING SLOTS FOR TEACHERS
        $this->createTeachersBookingSlots();

        //BOOK RANDOM SLOTS
        $this->createBookingsWithTeachers();
    }

    private function allRolesExist(): bool
    {
        $requiredRoles = collect(UserRole::cases())
            ->map(fn ($role) => $role->value);

        $existingRoles = Role::whereIn('name', $requiredRoles)
            ->pluck('name');

        return $requiredRoles->diff($existingRoles)->isEmpty();
    }

    private function seedUsers()
    {
        //TEACHERS
        $this->teachers = User::factory()->count(20)->create();
        $this->teachers->each(fn (User $teacher) => $teacher->assignRole(UserRole::TEACHER->value));

        //PARENTS
        $parents = User::factory()->count(20)->create();
        $parents->each(fn (User $parent) => $parent->assignRole(UserRole::PARENT->value));
    }

    private function createTeachersBookingSlots()
    {
        $data = new SlotData(
            days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            start_date: Carbon::now(),
            end_date: Carbon::now()->addDays(6),
            start_time: '09:00',
            end_time: '21:00',
        );

        $this->teachers->each(function (User $teacher) use ($data) {
            $slotFactoryService = new SlotFactory();
            $slotFactoryService->createAvailabilitySlots($data, $teacher);
        });
    }

    private function createBookingsWithTeachers()
    {
        $parents = User::role(UserRole::PARENT->value)->get();

        $dates = CarbonPeriod::create(Carbon::now(), Carbon::now()->addWeeks(2))
            ->filter(fn (Carbon $date) => $date->isWeekday());

        $this->teachers->each(function (User $teacher) use ($parents, $dates) {
            foreach ($dates as $date) {
                $slots = $teacher->getBookableSlots($date->toDateString(), 10, 0);

                if (empty($slots)) {
                    continue;
                }

                $slot   = collect($slots)->random();
                $parent = $parents->random();

                Zap::for($teacher)
                    ->named('Parent Evening')
                    ->appointment()
                    ->from($date->toDateString())
                    ->addPeriod($slot['start_time'], $slot['end_time'])
                    ->withMetadata(['parent_id' => $parent->id, 'type' => 'meeting-with-parent'])
                    ->save();
            }
        });
    }
}
