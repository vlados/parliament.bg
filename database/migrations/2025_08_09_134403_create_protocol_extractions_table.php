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
        Schema::create('protocol_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transcript_id')->constrained()->onDelete('cascade');
            $table->string('extraction_type', 50)->index();
            $table->json('extracted_data');
            $table->dateTime('extraction_date');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Index for finding extractions by transcript and type
            $table->index(['transcript_id', 'extraction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('protocol_extractions');
    }
};