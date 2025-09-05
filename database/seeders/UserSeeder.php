<?php

namespace Database\Seeders;

use App\UserRole;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Common password
        $password = Hash::make('12345678');

        // 2 Public Users
        User::factory(2)->state([
            'role' => UserRole::PUBLIC_USER,
            'password' => $password,
        ])->create();

        // 2 Healthcare Professionals
        User::factory(2)->state([
            'role' => UserRole::HEALTHCARE_PROFESSIONAL,
            'password' => $password,
        ])->create();

        // 2 Health Campaign Managers
        User::factory(2)->state([
            'role' => UserRole::HEALTH_CAMPAIGN_MANAGER,
            'password' => $password,
        ])->create();

        // 2 System Admins
        User::factory(2)->state([
            'role' => UserRole::SYSTEM_ADMIN,
            'password' => $password,
        ])->create();
    }
}
