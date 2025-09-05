<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cannot_create_overlapping_event_for_same_creator(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $existing = Event::factory()->create([
            'created_by' => $manager->id,
            'start_datetime' => now()->addDay()->setTime(10,0),
            'end_datetime' => now()->addDay()->setTime(12,0),
        ]);

        $payload = [
            'title' => 'Overlap',
            'description' => 'x',
            'type' => 'webinar',
            'status' => 'draft',
            'start_datetime' => now()->addDay()->setTime(11,0)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDay()->setTime(13,0)->format('Y-m-d H:i:s'),
            'requires_registration' => true,
            'is_online' => false,
        ];

        $this->actingAs($manager)
            ->post(route('events.store'), $payload)
            ->assertSessionHasErrors('start_datetime');
    }

    public function test_can_create_non_overlapping_event(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        Event::factory()->create([
            'created_by' => $manager->id,
            'start_datetime' => now()->addDay()->setTime(10,0),
            'end_datetime' => now()->addDay()->setTime(12,0),
        ]);

        $payload = [
            'title' => 'No overlap',
            'description' => 'x',
            'type' => 'webinar',
            'status' => 'draft',
            'start_datetime' => now()->addDay()->setTime(12,0)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDay()->setTime(14,0)->format('Y-m-d H:i:s'),
            'requires_registration' => true,
            'is_online' => false,
        ];

        $this->actingAs($manager)
            ->post(route('events.store'), $payload)
            ->assertRedirect(route('events.index'));
    }

    public function test_cannot_update_to_overlapping_time(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $a = Event::factory()->create([
            'created_by' => $manager->id,
            'start_datetime' => now()->addDays(2)->setTime(9,0),
            'end_datetime' => now()->addDays(2)->setTime(10,0),
        ]);
        $b = Event::factory()->create([
            'created_by' => $manager->id,
            'start_datetime' => now()->addDays(2)->setTime(11,0),
            'end_datetime' => now()->addDays(2)->setTime(12,0),
        ]);

        $this->actingAs($manager)
            ->put(route('events.update', $b), [
                'title' => $b->title,
                'description' => $b->description,
                'type' => $b->type,
                'status' => $b->status,
                'start_datetime' => now()->addDays(2)->setTime(9,30)->format('Y-m-d H:i:s'),
                'end_datetime' => now()->addDays(2)->setTime(10,30)->format('Y-m-d H:i:s'),
                'requires_registration' => $b->requires_registration,
                'is_online' => $b->is_online,
            ])
            ->assertSessionHasErrors('start_datetime');
    }
}
