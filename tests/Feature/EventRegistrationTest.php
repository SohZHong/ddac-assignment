<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\EventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_register_for_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $event = Event::factory()->create([
            'capacity' => 10,
            'requires_registration' => true,
        ]);

        $this->actingAs($user)
            ->post(route('events.registrations.store', $event))
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'registered',
        ]);
    }

    public function test_cannot_register_when_at_capacity(): void
    {
        $event = Event::factory()->create([
            'capacity' => 1,
            'requires_registration' => true,
        ]);

        $user1 = User::factory()->create(['email_verified_at' => now()]);
        $user2 = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user1)
            ->post(route('events.registrations.store', $event))
            ->assertRedirect();

        $this->actingAs($user2)
            ->post(route('events.registrations.store', $event))
            ->assertSessionHasErrors('registration');
    }

    public function test_user_cannot_register_twice(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $event = Event::factory()->create([
            'capacity' => 10,
            'requires_registration' => true,
        ]);

        $this->actingAs($user)
            ->post(route('events.registrations.store', $event))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('events.registrations.store', $event))
            ->assertRedirect();

        $this->assertEquals(1, EventRegistration::where('event_id', $event->id)->where('user_id', $user->id)->count());
    }

    public function test_user_can_unregister(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $event = Event::factory()->create([
            'capacity' => 10,
            'requires_registration' => true,
        ]);

        $this->actingAs($user)->post(route('events.registrations.store', $event));

        $this->actingAs($user)
            ->delete(route('events.registrations.destroy', $event))
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseMissing('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }
}
