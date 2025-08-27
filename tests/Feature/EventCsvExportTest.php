<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\EventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCsvExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_manager_can_export_registrations_csv(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $event = Event::factory()->create(['created_by' => $manager->id, 'requires_registration' => true]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($manager)->post(route('events.registrations.store', $event), ['user_id' => $user->id]);

        $response = $this->actingAs($manager)->get(route('events.registrations.export', $event));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv');
        $this->assertStringContainsString('User ID,Name,Email,Status,Registered At', $response->streamedContent());
        $this->assertStringContainsString($user->email, $response->streamedContent());
    }

    public function test_manager_can_export_attendances_csv(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $event = Event::factory()->create(['created_by' => $manager->id, 'requires_registration' => true]);
        $user = User::factory()->create(['email_verified_at' => now()]);

        // Register and check-in
        $this->actingAs($manager)->post(route('events.registrations.store', $event), ['user_id' => $user->id]);
        $this->actingAs($manager)->post(route('events.attendances.store', $event), ['user_id' => $user->id]);

        $response = $this->actingAs($manager)->get(route('events.attendances.export', $event));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv');
        $this->assertStringContainsString('User ID,Name,Email,Check-in Time,Check-out Time', $response->streamedContent());
        $this->assertStringContainsString($user->email, $response->streamedContent());
    }
}
