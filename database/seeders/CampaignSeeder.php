<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campaign;
use App\Models\User;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a campaign manager user
        $user = User::where('role', 3)->first();

        if (!$user) {
            $this->command->info('No campaign manager found. Creating one...');
            $user = User::create([
                'name' => 'Campaign Manager',
                'email' => 'campaign@example.com',
                'password' => bcrypt('password'),
                'role' => 3,
                'approval_status' => 'approved',
                'email_verified_at' => now(),
            ]);
        }

        // Create some sample campaigns
        $campaigns = [
            [
                'title' => 'Diabetes Prevention Awareness',
                'description' => 'A comprehensive campaign to raise awareness about diabetes prevention through healthy lifestyle choices, regular check-ups, and early detection.',
                'type' => 'Disease Prevention',
                'status' => 'active',
                'start_date' => '2024-01-15',
                'end_date' => '2024-03-15',
                'target_audience' => 'Adults 40+',
                'target_reach' => 5000,
                'budget' => 15000.00,
                'location' => 'New York City',
                'created_by' => $user->id,
            ],
            [
                'title' => 'Mental Health Support Initiative',
                'description' => 'Promoting mental health awareness and providing resources for stress management, anxiety, and depression support.',
                'type' => 'Mental Health',
                'status' => 'active',
                'start_date' => '2024-02-01',
                'end_date' => '2024-04-01',
                'target_audience' => 'Young Adults 18-35',
                'target_reach' => 3000,
                'budget' => 12000.00,
                'location' => 'Los Angeles',
                'created_by' => $user->id,
            ],
            [
                'title' => 'Vaccination Drive 2024',
                'description' => 'Annual vaccination campaign to ensure community immunity against common preventable diseases.',
                'type' => 'Vaccination',
                'status' => 'draft',
                'start_date' => '2024-03-01',
                'end_date' => '2024-05-01',
                'target_audience' => 'All Ages',
                'target_reach' => 10000,
                'budget' => 25000.00,
                'location' => 'Chicago',
                'created_by' => $user->id,
            ],
            [
                'title' => 'Nutrition Education Program',
                'description' => 'Teaching healthy eating habits and nutrition basics to families and children.',
                'type' => 'Nutrition',
                'status' => 'completed',
                'start_date' => '2023-09-01',
                'end_date' => '2023-11-30',
                'target_audience' => 'Families with Children',
                'target_reach' => 2000,
                'budget' => 8000.00,
                'location' => 'Miami',
                'created_by' => $user->id,
            ],
            [
                'title' => 'Exercise for Seniors',
                'description' => 'Promoting physical activity and exercise programs specifically designed for senior citizens.',
                'type' => 'Exercise',
                'status' => 'active',
                'start_date' => '2024-01-01',
                'end_date' => '2024-06-30',
                'target_audience' => 'Seniors 65+',
                'target_reach' => 1500,
                'budget' => 6000.00,
                'location' => 'Phoenix',
                'created_by' => $user->id,
            ]
        ];

        foreach ($campaigns as $campaignData) {
            Campaign::create($campaignData);
        }

        $this->command->info('Created ' . count($campaigns) . ' sample campaigns for user: ' . $user->name);
    }
}
