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
        Schema::create('video_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->string('meeting_id')->index();
            $table->string('committee_id')->index();
            $table->string('video_filename');
            $table->string('video_filepath');
            $table->text('transcription_text')->nullable();
            $table->string('language_code', 10)->nullable();
            $table->decimal('language_probability', 5, 4)->nullable();
            $table->json('word_timestamps')->nullable(); // Store word-level timestamps
            $table->json('speaker_diarization')->nullable(); // Store speaker identification if available
            $table->string('elevenlabs_model_id')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->decimal('api_cost', 8, 6)->nullable(); // Track API usage cost
            $table->integer('audio_duration_seconds')->nullable();
            $table->integer('file_size_bytes')->nullable();
            $table->json('api_response_metadata')->nullable(); // Store full API response
            $table->timestamp('transcription_started_at')->nullable();
            $table->timestamp('transcription_completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['committee_id', 'meeting_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_transcriptions');
    }
};
