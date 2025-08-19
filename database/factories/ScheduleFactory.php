<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Pick a random day of the week (0-6)
        $dayOfWeek = $this->faker->numberBetween(0, 6);

        // Random start time between 8am - 5pm
        $startTime = $this->faker->dateTimeBetween('08:00:00', '16:00:00');
        $endTime = (clone $startTime)->modify('+1 hour');

        // Get a healthcare professional ID, or create one if none exist
        $healthcareId = User::where('role', UserRole::HEALTHCARE_PROFESSIONAL)
            ->inRandomOrder()
            ->first()?->id 
            ?? User::factory()->healthcareProfessional();

        return [
            'healthcare_id' => $healthcareId,
            'day_of_week'   => $dayOfWeek,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
        ];
    }
}
