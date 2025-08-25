<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists
        $admin = User::where('email', 'admin@example.com')->first();
        
        if ($admin) {
            $this->command->info('Admin user already exists');
            $this->command->info('Email: admin@example.com');
            $this->command->info('Current Role: ' . $admin->role->label());
            return;
        }

        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin123!'),
            'role' => '4', // System Admin role
            'email_verified_at' => now(),
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: Admin123!');
    }
}
