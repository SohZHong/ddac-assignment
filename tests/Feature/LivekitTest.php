<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\LivekitRoom;
use App\Services\LivekitTokenService;
use App\Services\LivekitRoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LivekitTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_livekit_room()
    {
        $user = User::factory()->create(['role' => '3']); // Health Campaign Manager
        $event = Event::factory()->create(['created_by' => $user->id]);

        $roomService = new LivekitRoomService();
        $room = $roomService->createRoom($event, $user);

        $this->assertInstanceOf(LivekitRoom::class, $room);
        $this->assertEquals($event->id, $room->event_id);
        $this->assertEquals($user->id, $room->created_by);
        $this->assertEquals('scheduled', $room->status);
        $this->assertStringStartsWith('event-' . $event->id . '-', $room->room_name);
    }

    public function test_can_generate_token()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $room = LivekitRoom::factory()->create([
            'event_id' => $event->id,
            'created_by' => $user->id,
        ]);

        $tokenService = new LivekitTokenService();
        $token = $tokenService->generateToken($user, $room, 'test-identity');

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_room_creator_can_start_room()
    {
        $user = User::factory()->create(['role' => '3']); // Health Campaign Manager
        $event = Event::factory()->create(['created_by' => $user->id]);
        $room = LivekitRoom::factory()->create([
            'event_id' => $event->id,
            'created_by' => $user->id,
            'status' => 'scheduled',
        ]);

        $roomService = new LivekitRoomService();
        $roomService->startRoom($room);

        $this->assertEquals('live', $room->fresh()->status);
        $this->assertNotNull($room->fresh()->started_at);
    }

    public function test_room_creator_can_end_room()
    {
        $user = User::factory()->create(['role' => '3']); // Health Campaign Manager
        $event = Event::factory()->create(['created_by' => $user->id]);
        $room = LivekitRoom::factory()->create([
            'event_id' => $event->id,
            'created_by' => $user->id,
            'status' => 'live',
        ]);

        $roomService = new LivekitRoomService();
        $roomService->endRoom($room);

        $this->assertEquals('ended', $room->fresh()->status);
        $this->assertNotNull($room->fresh()->ended_at);
    }

    public function test_room_has_relationships()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $room = LivekitRoom::factory()->create([
            'event_id' => $event->id,
            'created_by' => $user->id,
        ]);

        $this->assertInstanceOf(Event::class, $room->event);
        $this->assertInstanceOf(User::class, $room->creator);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $room->participants);
    }
}
