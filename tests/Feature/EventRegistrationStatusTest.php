<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\EventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_manager_can_update_registration_status(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $event = Event::factory()->create(['created_by' => $manager->id, 'capacity' => 10, 'requires_registration' => true]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Add registration as manager
        $this->actingAs($manager)->post(route('events.registrations.store', $event), ['user_id' => $user->id]);
        $registration = EventRegistration::where('event_id', $event->id)->where('user_id', $user->id)->firstOrFail();

        // Update status to confirmed
        $this->actingAs($manager)
            ->patch(route('events.registrations.update', [$event, $registration]), ['status' => 'confirmed'])
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('event_registrations', [
            'id' => $registration->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_capacity_enforced_on_status_change_into_counting(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $event = Event::factory()->create(['created_by' => $manager->id, 'capacity' => 1, 'requires_registration' => true]);

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        // Fill capacity with user1
        $this->actingAs($manager)->post(route('events.registrations.store', $event), ['user_id' => $user1->id])->assertRedirect();

        // Second user will be waitlisted automatically at capacity
        $this->actingAs($manager)->post(route('events.registrations.store', $event), ['user_id' => $user2->id])->assertRedirect();
        $registration2 = EventRegistration::where('event_id', $event->id)->where('user_id', $user2->id)->firstOrFail();
        $this->assertEquals('waitlisted', $registration2->status);

        // Try to move waitlisted -> registered (should fail due to capacity)
        $this->actingAs($manager)
            ->patch(route('events.registrations.update', [$event, $registration2]), ['status' => 'registered'])
            ->assertSessionHasErrors('registration');
    }
}
