<?php

namespace Database\Factories;

use App\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::PUBLIC_USER, // Default role
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with healthcare professional role.
     */
    public function healthcareProfessional(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::HEALTHCARE_PROFESSIONAL,
        ]);
    }

    /**
     * Create a user with health campaign manager role.
     */
    public function healthCampaignManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::HEALTH_CAMPAIGN_MANAGER,
        ]);
    }

    /**
     * Create a user with system admin role.
     */
    public function systemAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SYSTEM_ADMIN,
        ]);
    }
}
