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
            'approval_status' => 'approved', // Default to approved for testing
            'approved_at' => now(),
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
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Create a user with health campaign manager role.
     */
    public function healthCampaignManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::HEALTH_CAMPAIGN_MANAGER,
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Create a user with system admin role.
     */
    public function systemAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SYSTEM_ADMIN,
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Create a user with public user role.
     */
    public function publicUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::PUBLIC_USER,
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Create a user with random role
     */
    public function randomRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => fake()->randomElement([
                UserRole::PUBLIC_USER,
                UserRole::HEALTHCARE_PROFESSIONAL,
                UserRole::HEALTH_CAMPAIGN_MANAGER,
                UserRole::SYSTEM_ADMIN,
            ]),
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Create a user that needs approval (pending)
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'pending',
            'approved_at' => null,
        ]);
    }

    /**
     * Create a user that is approved
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Create a user that is rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_status' => 'rejected',
            'approved_at' => null,
            'rejection_reason' => 'Test rejection reason',
        ]);
    }
}
