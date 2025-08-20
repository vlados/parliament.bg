<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoTranscription extends Model
{
    protected $fillable = [
        'meeting_id',
        'committee_id',
        'video_filename',
        'video_filepath',
        'transcription_text',
        'language_code',
        'language_probability',
        'word_timestamps',
        'speaker_diarization',
        'elevenlabs_model_id',
        'status',
        'error_message',
        'api_cost',
        'audio_duration_seconds',
        'file_size_bytes',
        'api_response_metadata',
        'transcription_started_at',
        'transcription_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'word_timestamps' => 'array',
            'speaker_diarization' => 'array',
            'api_response_metadata' => 'array',
            'language_probability' => 'decimal:4',
            'api_cost' => 'decimal:6',
            'transcription_started_at' => 'datetime',
            'transcription_completed_at' => 'datetime',
        ];
    }

    /**
     * Get the committee that owns this transcription
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id', 'committee_id');
    }

    /**
     * Check if transcription is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transcription failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if transcription is in progress
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Get transcription duration in human readable format
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->audio_duration_seconds) {
            return null;
        }

        $hours = floor($this->audio_duration_seconds / 3600);
        $minutes = floor(($this->audio_duration_seconds % 3600) / 60);
        $seconds = $this->audio_duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size_bytes) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size_bytes;
        
        for ($i = 0; $bytes > 1024 && $i < 3; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
