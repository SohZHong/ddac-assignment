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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_temp')->nullable();
        });

        DB::statement("
            UPDATE users SET role_temp = CASE 
                WHEN role = 'public_user' THEN 1
                WHEN role = 'healthcare_professional' THEN 2
                WHEN role = 'health_campaign_manager' THEN 3
                WHEN role = 'system_admin' THEN 4
                ELSE 1
            END
        ");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_temp', 'role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role')->default(1)->change();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT role_check CHECK (role IN (1, 2, 3, 4));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS role_check;");
        
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role_temp', ['public_user', 'healthcare_professional', 'health_campaign_manager', 'system_admin'])->nullable();
        });

        DB::statement("
            UPDATE users SET role_temp = CASE 
                WHEN role = 1 THEN 'public_user'
                WHEN role = 2 THEN 'healthcare_professional'
                WHEN role = 3 THEN 'health_campaign_manager'
                WHEN role = 4 THEN 'system_admin'
                ELSE 'public_user'
            END
        ");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_temp', 'role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['public_user', 'healthcare_professional', 'health_campaign_manager', 'system_admin'])->default('public_user')->change();
        });
    }
};
