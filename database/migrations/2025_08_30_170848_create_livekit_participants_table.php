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
        Schema::create('livekit_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('livekit_rooms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('participant_identity');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livekit_participants');
    }
};
