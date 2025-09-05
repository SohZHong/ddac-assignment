<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays($this->faker->numberBetween(1, 30))->setTime($this->faker->numberBetween(8, 18), [0, 30][array_rand([0,1])]);
        $end = (clone $start)->addMinutes($this->faker->randomElement([60, 90, 120, 150, 180]));

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['webinar', 'health_event', 'check_up_drive', 'workshop', 'seminar']),
            'status' => 'draft',
            'start_datetime' => $start,
            'end_datetime' => $end,
            'location' => $this->faker->optional()->address(),
            'online_meeting_url' => null,
            'capacity' => $this->faker->optional()->numberBetween(10, 200),
            'is_online' => false,
            'requires_registration' => true,
            'metadata' => null,
            'campaign_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
