<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $campaignTypes = [
            'Heart Attack Awareness',
            'CVD Risk Assessment',
            'Healthy Lifestyle Education',
            'Anti-Smoking/Vaping Campaigns',
            'Nutrition Education',
            'Physical Activity Promotion',
            'Stress Management',
            'Early Detection Programs',
            'School-Based Interventions',
            'Community Outreach',
            'Digital Health Initiatives',
            'Policy Advocacy',
        ];

        $statuses = ['draft', 'active', 'completed', 'cancelled'];
        $startDate = $this->faker->dateTimeBetween('-6 months', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+6 months');

        return [
            'title' => $this->faker->sentence(3, 6),
            'description' => $this->faker->paragraphs(3, true),
            'type' => $this->faker->randomElement($campaignTypes),
            'status' => $this->faker->randomElement($statuses),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'target_audience' => $this->faker->randomElement([
                'General Public',
                'Adults 18-65',
                'Seniors 65+',
                'Children and Youth',
                'Healthcare Professionals',
                'Students',
                'Working Professionals',
                'Rural Communities',
                'Urban Communities',
            ]),
            'target_reach' => $this->faker->numberBetween(100, 10000),
            'budget' => $this->faker->randomFloat(2, 1000, 50000),
            'location' => $this->faker->randomElement([
                'National',
                'State-wide',
                'City-wide',
                'Community-based',
                'Online',
                'Hybrid',
            ]),
            'metadata' => [
                'social_media_channels' => $this->faker->randomElements(['Facebook', 'Twitter', 'Instagram', 'LinkedIn', 'YouTube'], $this->faker->numberBetween(1, 3)),
                'partners' => $this->faker->words(3),
                'success_metrics' => $this->faker->sentences(3),
            ],
            'created_by' => User::factory()->healthCampaignManager(),
        ];
    }

    /**
     * Indicate that the campaign is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->subDays(rand(1, 30)),
            'end_date' => now()->addDays(rand(1, 90)),
        ]);
    }

    /**
     * Indicate that the campaign is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the campaign is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => now()->subDays(rand(60, 180)),
            'end_date' => now()->subDays(rand(1, 59)),
        ]);
    }

    /**
     * Indicate that the campaign is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the campaign is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'location' => 'Online',
            'metadata' => [
                'social_media_channels' => ['Facebook', 'Twitter', 'Instagram', 'LinkedIn', 'YouTube'],
                'online_platforms' => ['Zoom', 'Teams', 'Webinar'],
                'digital_tools' => ['Email Marketing', 'SMS Campaigns', 'Social Media Ads'],
            ],
        ]);
    }
}
