<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LivekitRoom>
 */
class LivekitRoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_name' => 'event-' . fake()->numberBetween(1, 1000) . '-' . Str::random(8),
            'event_id' => Event::factory(),
            'created_by' => User::factory(),
            'status' => fake()->randomElement(['scheduled', 'live', 'ended', 'cancelled']),
            'max_participants' => fake()->numberBetween(10, 200),
            'started_at' => fake()->optional()->dateTime(),
            'ended_at' => fake()->optional()->dateTime(),
        ];
    }

    /**
     * Indicate that the room is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'started_at' => null,
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the room is live.
     */
    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'live',
            'started_at' => now(),
            'ended_at' => null,
        ]);
    }

    /**
     * Indicate that the room has ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ended',
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subMinutes(30),
        ]);
    }
}
