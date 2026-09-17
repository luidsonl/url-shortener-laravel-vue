<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => UserRole::ADMIN,
            ]
        );

        // Regular users
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ],
            [
                'name' => 'Carlos Pereira',
                'email' => 'carlos@example.com',
            ],
            [
                'name' => 'Maria Oliveira',
                'email' => 'maria@example.com',
            ],
            [
                'name' => 'Lucas Andrade',
                'email' => 'lucas@example.com',
            ],
            [
                'name' => 'Fernanda Costa',
                'email' => 'fernanda@example.com',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role' => UserRole::USER,
                ]
            );
        }
    }
}
