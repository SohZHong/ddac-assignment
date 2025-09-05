<?php

namespace Database\Seeders;

use App\Models\HealthMetric;
use App\Models\User;
use Illuminate\Database\Seeder;

class HealthMetricSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            HealthMetric::create([
                'user_id' => $user->id,
                'metric_type' => 'weight',
                'value' => rand(60, 120),
                'unit' => 'kg',
                'recorded_at' => now()->subDays(rand(1, 30)),
            ]);

            HealthMetric::create([
                'user_id' => $user->id,
                'metric_type' => 'blood_pressure_systolic',
                'value' => rand(90, 180),
                'unit' => 'mmHg',
                'recorded_at' => now()->subDays(rand(1, 30)),
            ]);

            HealthMetric::create([
                'user_id' => $user->id,
                'metric_type' => 'blood_pressure_diastolic',
                'value' => rand(60, 100),
                'unit' => 'mmHg',
                'recorded_at' => now()->subDays(rand(1, 30)),
            ]);

            HealthMetric::create([
                'user_id' => $user->id,
                'metric_type' => 'blood_sugar',
                'value' => rand(70, 200),
                'unit' => 'mg/dL',
                'recorded_at' => now()->subDays(rand(1, 30)),
            ]);

            HealthMetric::create([
                'user_id' => $user->id,
                'metric_type' => 'heart_rate',
                'value' => rand(50, 120),
                'unit' => 'bpm',
                'recorded_at' => now()->subDays(rand(1, 30)),
            ]);

            HealthMetric::create([
                'user_id' => $user->id,
                'metric_type' => 'cholesterol',
                'value' => rand(150, 300),
                'unit' => 'mg/dL',
                'recorded_at' => now()->subDays(rand(1, 30)),
            ]);

            HealthMetric::create([
                'user_id' => $user->id,
                'metric_type' => 'temperature',
                'value' => rand(360, 390) / 10,
                'unit' => '°C',
                'recorded_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
