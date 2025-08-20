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
        Schema::create('bill_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transcript_id');
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->string('bill_identifier')->nullable();
            $table->string('proposer_name')->nullable();
            $table->enum('amendment_type', ['new', 'modification', 'deletion'])->nullable();
            $table->text('amendment_description')->nullable();
            $table->enum('status', ['proposed', 'approved', 'rejected', 'pending'])->default('pending');
            $table->json('vote_results')->nullable();
            $table->decimal('ai_confidence', 5, 4)->nullable();
            $table->longText('raw_context')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('transcript_id')->references('id')->on('transcripts')->onDelete('cascade');
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('set null');
            
            $table->index(['transcript_id', 'bill_id']);
            $table->index('bill_identifier');
            $table->index('status');
            $table->index('ai_confidence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_analyses');
    }
};
