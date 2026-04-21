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
        $users = [
            [
                'name' => 'Ferhnir',
                'email' => 'zax1984@gmail.com',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Monterek',
                'email' => 'monterek@example.com',
                'password' => Hash::make('password123'),
            ]
        ];

        foreach($users as $userElement){
            $user = User::query()->firstOrCreate(['email' => $userElement['email']], $userElement);

            $user->assignRole(UserRole::ADMIN->value);
        }
    }
}
