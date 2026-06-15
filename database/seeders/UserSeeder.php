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
            ],
            [
                'name' => 'Marian Rudy',
                'email' => 'marian.rudy@gmail.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Mariola Jola',
                'email' => 'mariola.jola@gmail.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Monter Tlen',
                'email' => 'monterek5@o2.pl',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        ];

        foreach($parents as $parent){
            $user = User::query()->firstOrCreate(['email' => $parent['email']], $parent);

            $user->assignRole(UserRole::PARENT->value);
        }

        $teachers = [
            [
                'name' => 'Mr Ozy Hillow',
                'email' => 'hillow@gmail.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Jan Novwak',
                'email' => 'nowak@gmail.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Pawel Monter',
                'email' => 'monterek6@gmail.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        foreach($teachers as $teacher){
            $user = User::query()->firstOrCreate(['email' => $teacher['email']], $teacher);

            $user->assignRole(UserRole::TEACHER->value);
        }
    }
}
