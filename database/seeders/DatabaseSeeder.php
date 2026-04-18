<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
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

        User::insert($users);
    }
}
