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
        Schema::create('professional_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('credential_type'); // e.g., 'medical_license', 'board_certification', 'campaign_certification'
            $table->string('credential_number')->nullable(); // License/certification number
            $table->string('issuing_authority'); // Who issued the credential
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('document_path'); // Path to uploaded credential document
            $table->text('additional_info')->nullable(); // Any additional information
            $table->timestamps();
            
            // Add indexes for faster queries
            $table->index(['user_id', 'credential_type']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_credentials');
    }
};
