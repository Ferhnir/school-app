<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;

#[Signature('app:generate-fake-users')]
#[Description('Generate fake users for testing')]
class GenerateFakeUsers extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roles = Role::all(['name']);

        $users = User::factory()->count(20)->create();

        $this->info('Created ' . $users->count() . ' users');
        $this->info('Assigning random roles');

        foreach($users as $user)
        {
            $user->assignRole(Arr::random($roles->toArray()));
        }
    }
}
