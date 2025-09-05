<?php

namespace Database\Seeders;

use App\Models\HealthMetric;
use App\Models\HealthRecommendation;
use App\Models\User;
use Illuminate\Database\Seeder;

class HealthDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(5)->get();
        
        foreach ($users as $user) {
            for ($i = 0; $i < 10; $i++) {
                HealthMetric::create([
                    'user_id' => $user->id,
                    'metric_type' => 'weight',
                    'value' => rand(60, 90),
                    'unit' => 'kg',
                    'recorded_at' => now()->subDays(rand(1, 90)),
                ]);

                HealthMetric::create([
                    'user_id' => $user->id,
                    'metric_type' => 'blood_pressure_systolic',
                    'value' => rand(110, 160),
                    'unit' => 'mmHg',
                    'recorded_at' => now()->subDays(rand(1, 90)),
                ]);

                HealthMetric::create([
                    'user_id' => $user->id,
                    'metric_type' => 'blood_sugar',
                    'value' => rand(80, 150),
                    'unit' => 'mg/dL',
                    'recorded_at' => now()->subDays(rand(1, 90)),
                ]);
            }

            HealthRecommendation::create([
                'user_id' => $user->id,
                'title' => 'Daily Exercise',
                'description' => 'Aim for 30 minutes of moderate exercise daily.',
                'category' => 'fitness',
                'priority' => 'medium',
                'status' => 'active',
                'generated_by' => 'system'
            ]);

            HealthRecommendation::create([
                'user_id' => $user->id,
                'title' => 'Hydration Goal',
                'description' => 'Drink at least 8 glasses of water per day.',
                'category' => 'nutrition',
                'priority' => 'low',
                'status' => 'active',
                'generated_by' => 'system'
            ]);
        }
    }
}
