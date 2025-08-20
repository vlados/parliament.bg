<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillAnalysis extends Model
{
    protected $fillable = [
        'transcript_id',
        'bill_id',
        'bill_identifier',
        'proposer_name',
        'amendment_type',
        'amendment_description',
        'status',
        'vote_results',
        'ai_confidence',
        'raw_context',
        'metadata',
    ];

    protected $casts = [
        'vote_results' => 'array',
        'ai_confidence' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Get the transcript that this analysis belongs to
     */
    public function transcript(): BelongsTo
    {
        return $this->belongsTo(Transcript::class);
    }

    /**
     * Get the bill that this analysis references (if linked)
     */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by amendment type
     */
    public function scopeByAmendmentType($query, string $type)
    {
        return $query->where('amendment_type', $type);
    }

    /**
     * Scope to filter by confidence level
     */
    public function scopeHighConfidence($query, float $threshold = 0.8)
    {
        return $query->where('ai_confidence', '>=', $threshold);
    }

    /**
     * Scope to filter by low confidence level
     */
    public function scopeLowConfidence($query, float $threshold = 0.5)
    {
        return $query->where('ai_confidence', '<', $threshold);
    }

    /**
     * Get formatted vote results
     */
    public function getFormattedVoteResultsAttribute(): ?string
    {
        if (!$this->vote_results) {
            return null;
        }

        $results = [];
        if (isset($this->vote_results['for'])) {
            $results[] = "За: {$this->vote_results['for']}";
        }
        if (isset($this->vote_results['against'])) {
            $results[] = "Против: {$this->vote_results['against']}";
        }
        if (isset($this->vote_results['abstained'])) {
            $results[] = "Въздържали се: {$this->vote_results['abstained']}";
        }

        return implode(', ', $results);
    }

    /**
     * Get confidence level as percentage
     */
    public function getConfidencePercentageAttribute(): ?string
    {
        if ($this->ai_confidence === null) {
            return null;
        }

        return number_format($this->ai_confidence * 100, 1) . '%';
    }

    /**
     * Get confidence level color for UI
     */
    public function getConfidenceColorAttribute(): string
    {
        if ($this->ai_confidence === null) {
            return 'gray';
        }

        if ($this->ai_confidence >= 0.8) {
            return 'success';
        }

        if ($this->ai_confidence >= 0.6) {
            return 'warning';
        }

        return 'danger';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'proposed' => 'info',
            'pending' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Get status label in Bulgarian
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Одобрено',
            'rejected' => 'Отхвърлено',
            'proposed' => 'Предложено',
            'pending' => 'В очакване',
            default => 'Неизвестно',
        };
    }

    /**
     * Get amendment type label in Bulgarian
     */
    public function getAmendmentTypeLabelAttribute(): ?string
    {
        return match ($this->amendment_type) {
            'new' => 'Нов текст',
            'modification' => 'Изменение',
            'deletion' => 'Заличаване',
            default => null,
        };
    }
}
