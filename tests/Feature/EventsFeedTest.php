<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_feed_returns_events_in_date_range(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'role' => 4]); // system admin

        $within = Event::factory()->create([
            'created_by' => $admin->id,
            'start_datetime' => now()->startOfMonth()->addDays(5),
            'end_datetime' => now()->startOfMonth()->addDays(5)->addHours(2),
        ]);
        $outside = Event::factory()->create([
            'created_by' => $admin->id,
            'start_datetime' => now()->startOfMonth()->subDays(10),
            'end_datetime' => now()->startOfMonth()->subDays(10)->addHours(2),
        ]);

        $resp = $this->actingAs($admin)->get(route('events.feed', [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfMonth()->toDateString(),
        ]));

        $resp->assertOk();
        $data = $resp->json('events');
        $ids = collect($data)->pluck('id');
        $this->assertTrue($ids->contains($within->id));
        $this->assertFalse($ids->contains($outside->id));
    }

    public function test_manager_sees_only_own_events(): void
    {
        $manager1 = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);
        $manager2 = User::factory()->create(['email_verified_at' => now(), 'role' => 3]);

        $own = Event::factory()->create([
            'created_by' => $manager1->id,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHour(),
        ]);
        $others = Event::factory()->create([
            'created_by' => $manager2->id,
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHour(),
        ]);

        $resp = $this->actingAs($manager1)->get(route('events.feed', [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDays(2)->toDateString(),
        ]));

        $data = $resp->json('events');
        $ids = collect($data)->pluck('id');
        $this->assertTrue($ids->contains($own->id));
        $this->assertFalse($ids->contains($others->id));
    }
}
