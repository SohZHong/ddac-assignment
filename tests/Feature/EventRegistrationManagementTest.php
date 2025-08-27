<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\EventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_manager_can_add_and_remove_registration_for_their_event(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $event = Event::factory()->create(['created_by' => $manager->id, 'capacity' => 2, 'requires_registration' => true]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Manager adds user to event
        $this->actingAs($manager)
            ->post(route('events.registrations.store', $event), ['user_id' => $user->id])
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'registered',
        ]);

        // Manager removes user from event
        $this->actingAs($manager)
            ->delete(route('events.registrations.destroy', $event), ['user_id' => $user->id])
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseMissing('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_second_user_is_waitlisted_when_manager_adds_others_at_capacity(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $event = Event::factory()->create(['created_by' => $manager->id, 'capacity' => 1, 'requires_registration' => true]);

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($manager)->post(route('events.registrations.store', $event), ['user_id' => $user1->id])->assertRedirect();
        $this->actingAs($manager)->post(route('events.registrations.store', $event), ['user_id' => $user2->id])->assertRedirect();

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user2->id,
            'status' => 'waitlisted',
        ]);
    }
}
