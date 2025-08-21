<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use League\CommonMark\Node\Query;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('active')->default(false)->after('description');
        });

        // enforce only one active quiz per healthcare_id
        DB::statement('CREATE UNIQUE INDEX unique_active_quiz_per_doctor 
            ON quizzes (healthcare_id) 
            WHERE active = true');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('active');
        });

        DB::statement('DROP INDEX unique_active_quiz_per_doctor');
    }
};
