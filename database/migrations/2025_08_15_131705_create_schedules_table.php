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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('healthcare_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->integer('day_of_week');
            // start time = current time
            $table->timestamp('start_time')
                ->default(DB::raw('CURRENT_TIMESTAMP'));
            // end time = 2 hours after current time
            $table->timestamp('end_time')
                ->default(DB::raw("CURRENT_TIMESTAMP + interval '2 hours'"));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
