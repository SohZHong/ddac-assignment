<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // content_abuse, security, feedback
            $table->text('description');
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open'); // open, in_progress, resolved
            $table->json('context')->nullable(); // e.g., content id, URLs
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reports');
    }
};


