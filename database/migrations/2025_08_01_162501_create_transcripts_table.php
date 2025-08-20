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
        Schema::create('transcripts', function (Blueprint $table) {
            $table->id();
            $table->string('transcript_id')->unique()->comment('Transcript ID from API (t_id)');
            $table->string('committee_id')->index()->comment('Foreign key to committees table');
            $table->string('type')->comment('Transcript type from t_label');
            $table->date('transcript_date')->comment('Date from t_date');
            $table->integer('year')->index()->comment('Extracted year from date for faster queries');
            $table->integer('month')->index()->comment('Extracted month from date for faster queries');
            $table->longText('content_html')->nullable()->comment('Raw HTML content from API');
            $table->longText('content_text')->nullable()->comment('Extracted plain text from HTML');
            $table->integer('word_count')->nullable()->comment('Number of words in content_text');
            $table->integer('character_count')->nullable()->comment('Number of characters in content_text');
            $table->json('metadata')->nullable()->comment('Additional data from API response');
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('committee_id')->references('committee_id')->on('committees')->onDelete('cascade');
            
            // Indexes for better performance
            $table->index(['committee_id', 'year', 'month']);
            $table->index(['type', 'transcript_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transcripts');
    }
};
