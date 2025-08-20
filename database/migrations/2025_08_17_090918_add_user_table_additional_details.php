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
            $table->string('license_number')->nullable()->after('role');
            $table->string('medical_specialty')->nullable()->after('license_number');
            $table->string('institution_name')->nullable()->after('medical_specialty');
            $table->integer('years_experience')->nullable()->after('institution_name');
            $table->string('registration_body')->nullable()->after('years_experience');
            
            $table->string('organization_name')->nullable()->after('registration_body');
            $table->string('job_title')->nullable()->after('organization_name');
            $table->string('organization_type')->nullable()->after('job_title');
            $table->string('focus_areas')->nullable()->after('organization_type');
            
            $table->string('work_email')->nullable()->after('focus_areas');
            $table->boolean('is_verified')->default(false)->after('work_email'); 
            $table->timestamp('verified_at')->nullable()->after('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'license_number',
                'medical_specialty', 
                'institution_name',
                'years_experience',
                'registration_body',
                'organization_name',
                'job_title',
                'organization_type',
                'focus_areas',
                'work_email',
                'is_verified',
                'verified_at',
            ]);
        });
    }
};