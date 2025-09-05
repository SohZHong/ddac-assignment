<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\UserRole;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Blog>
 */
class BlogFactory extends Factory
{

    protected $model = Blog::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(6, true);

        $slug = Str::slug($title) . '-' . Str::random(5);

        $content = $this->faker->paragraphs(5, true);

        $coverImage =  $this->faker->imageUrl(800, 400, 'blog', true);

        // Get a healthcare professional or campaign master ID, or create one if none exist
        $authorId = User::whereIn('role', [
                UserRole::HEALTHCARE_PROFESSIONAL,
                UserRole::HEALTH_CAMPAIGN_MANAGER,
            ])
            ->inRandomOrder()
            ->first()?->id
            ?? User::factory()->state([
                'role' => fake()->randomElement([
                    UserRole::HEALTHCARE_PROFESSIONAL,
                    UserRole::HEALTH_CAMPAIGN_MANAGER,
                ]),
            ]);

        $status =  $this->faker->boolean(50); // 50% are published

        $published =  $this->faker->optional()->dateTimeBetween('-1 year', 'now'); // Between now and a year ago

        return [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'cover_image' => $coverImage,
            'author_id' => $authorId,
            'status' => $status,
            'published_at' =>$published,
        ];
    }
}
