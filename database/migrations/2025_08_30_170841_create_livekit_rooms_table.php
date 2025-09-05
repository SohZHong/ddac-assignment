<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('livekit_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->unique();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users');
            $table->enum('status', ['scheduled', 'live', 'ended', 'cancelled'])->default('scheduled');
            $table->integer('max_participants')->default(100);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livekit_rooms');
    }
};
