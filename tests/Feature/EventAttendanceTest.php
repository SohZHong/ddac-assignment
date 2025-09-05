<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\EventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_check_in_for_event_without_registration_requirement(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $event = Event::factory()->create([
            'requires_registration' => false,
        ]);

        $this->actingAs($user)
            ->post(route('events.attendances.store', $event))
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseHas('event_attendances', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'present',
        ]);
    }

    public function test_user_must_register_before_check_in_when_required(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $event = Event::factory()->create([
            'requires_registration' => true,
        ]);

        $this->actingAs($user)
            ->post(route('events.attendances.store', $event))
            ->assertSessionHasErrors('attendance');

        // Register then check-in
        $this->actingAs($user)
            ->post(route('events.registrations.store', $event))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('events.attendances.store', $event))
            ->assertRedirect(route('events.show', $event));
    }

    public function test_user_can_undo_check_in(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $event = Event::factory()->create([
            'requires_registration' => false,
        ]);

        $this->actingAs($user)->post(route('events.attendances.store', $event));

        $this->actingAs($user)
            ->delete(route('events.attendances.destroy', $event))
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseMissing('event_attendances', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_event_creator_can_list_attendances(): void
    {
        // Create a campaign manager
        $manager = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 3, // HEALTH_CAMPAIGN_MANAGER
        ]);

        // Create event owned by manager
        $event = Event::factory()->create([
            'created_by' => $manager->id,
            'requires_registration' => false,
        ]);

        // Create an attendee and check in
        $attendee = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($attendee)->post(route('events.attendances.store', $event));

        // Manager can list
        $response = $this->actingAs($manager)->get(route('events.attendances.index', $event));
        $response->assertOk();
        $response->assertJsonStructure([
            'attendances' => [
                [
                    'id',
                    'user' => ['id', 'name', 'email'],
                    'status',
                    'check_in_time',
                    'check_out_time',
                ],
            ],
        ]);
    }
}
