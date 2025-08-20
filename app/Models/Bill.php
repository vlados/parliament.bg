<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    protected $fillable = [
        'bill_id',
        'title',
        'sign',
        'signature',
        'bill_date',
        'session',
        'path',
        'committee_id',
        'submitters',
        'committees',
        'pdf_url',
        'pdf_filename',
        'extracted_text',
        'pdf_downloaded_at',
        'reports',
        'stenograms',
        'opinions',
        'is_detailed',
        'word_count',
        'character_count',
        'text_language'
    ];

    protected $casts = [
        'bill_date' => 'datetime',
        'pdf_downloaded_at' => 'datetime',
        'submitters' => 'array',
        'committees' => 'array',
        'reports' => 'array',
        'stenograms' => 'array',
        'opinions' => 'array',
        'is_detailed' => 'boolean',
    ];

    /**
     * Get the committee that handles this bill
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id', 'committee_id');
    }

    /**
     * Get the formatted submitters as a comma-separated string
     */
    public function getSubmittersStringAttribute(): string
    {
        if (!$this->submitters) {
            return 'Няма данни';
        }
        
        return implode('; ', $this->submitters);
    }

    /**
     * Get the leading committee from the committees array
     */
    public function getLeadingCommitteeAttribute(): ?string
    {
        if (!$this->committees || empty($this->committees)) {
            return null;
        }

        foreach ($this->committees as $committee) {
            if (str_contains($committee, '(водеща)')) {
                return str_replace(' (водеща)', '', $committee);
            }
        }

        return $this->committees[0] ?? null;
    }

    /**
     * Get participating committees (non-leading)
     */
    public function getParticipatingCommitteesAttribute(): array
    {
        if (!$this->committees || empty($this->committees)) {
            return [];
        }

        return array_filter($this->committees, function ($committee) {
            return str_contains($committee, '(участваща)');
        });
    }

    /**
     * Check if PDF has been downloaded and text extracted
     */
    public function hasPdfText(): bool
    {
        return !empty($this->extracted_text) && !empty($this->pdf_filename);
    }

    /**
     * Get word count (calculate if not stored)
     */
    public function getWordCountAttribute($value): int
    {
        if ($value !== null) {
            return $value;
        }

        if ($this->extracted_text) {
            return str_word_count($this->extracted_text);
        }

        return str_word_count($this->title ?? '');
    }

    /**
     * Get character count (calculate if not stored)
     */
    public function getCharacterCountAttribute($value): int
    {
        if ($value !== null) {
            return $value;
        }

        if ($this->extracted_text) {
            return strlen($this->extracted_text);
        }

        return strlen($this->title ?? '');
    }
}
