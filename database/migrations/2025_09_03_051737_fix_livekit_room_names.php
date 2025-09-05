<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix existing room names to use consistent pattern
        DB::table('livekit_rooms')->get()->each(function ($room) {
            $eventId = $room->event_id;
            $newRoomName = "event-{$eventId}";
            
            // Only update if the room name doesn't already match the pattern
            if (!str_starts_with($room->room_name, "event-{$eventId}")) {
                DB::table('livekit_rooms')
                    ->where('id', $room->id)
                    ->update(['room_name' => $newRoomName]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration fixes data, so no rollback needed
    }
};
