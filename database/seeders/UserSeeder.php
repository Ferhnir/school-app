<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Ferhnir',
                'email' => 'zax1984@gmail.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Monterek',
                'email' => 'monterek@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        ];

        foreach($admins as $userElement){
            $user = User::query()->firstOrCreate(['email' => $userElement['email']], $userElement);

            $user->assignRole(UserRole::ADMIN->value);
        }

        $parents = [
            [
                'name' => 'Max Zdunski',
                'email' => 'zdunskimaksymilian@gmail.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        ];

        foreach($parents as $parent){
            $user = User::query()->firstOrCreate(['email' => $parent['email']], $parent);

            $user->assignRole(UserRole::PARENT->value);
        }
    }
}
