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
        Schema::table('users', function (Blueprint $table) {
            // Ensure the 'role' column exists before modifying it
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            // Add the 'role' column with a default value
            $table->string('role')->default('public_user');
        });
    }

    public function down(): void
    {
    }
};
