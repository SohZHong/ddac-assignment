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
        Schema::table('bookings', function (Blueprint $table) {
            $table->longText('healthcare_comments')->nullable()->after('status');
            $table->unsignedTinyInteger('risk_level')->default(0)->after('healthcare_comments');
            // 0 = Low, 1 = Mid, 2 = High
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['healthcare_comments', 'risk_level']);
        });
    }
};
