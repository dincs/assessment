<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'System Admin',
                'password'          => Hash::make('password'),
                'is_admin'          => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
