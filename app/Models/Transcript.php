<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Transcript extends Model
{
    protected $fillable = [
        'transcript_id',
        'committee_id',
        'type',
        'transcript_date',
        'year',
        'month',
        'content_html',
        'content_text',
        'word_count',
        'character_count',
        'metadata',
    ];

    protected $casts = [
        'transcript_date' => 'date',
        'year' => 'integer',
        'month' => 'integer',
        'word_count' => 'integer',
        'character_count' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the committee that owns this transcript
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id', 'committee_id');
    }

    /**
     * Get the bill analyses for this transcript
     */
    public function billAnalyses(): HasMany
    {
        return $this->hasMany(BillAnalysis::class);
    }

    /**
     * Get the protocol extractions for this transcript
     */
    public function protocolExtractions(): HasMany
    {
        return $this->hasMany(ProtocolExtraction::class);
    }

    /**
     * Extract plain text from HTML content
     */
    public function extractTextFromHtml(): string
    {
        if (!$this->content_html) {
            return '';
        }

        // Remove HTML tags and decode HTML entities
        $text = strip_tags($this->content_html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Calculate word count from text content
     */
    public function calculateWordCount(): int
    {
        if (!$this->content_text) {
            return 0;
        }

        return str_word_count($this->content_text);
    }

    /**
     * Calculate character count from text content
     */
    public function calculateCharacterCount(): int
    {
        if (!$this->content_text) {
            return 0;
        }

        return mb_strlen($this->content_text, 'UTF-8');
    }

    /**
     * Auto-populate year and month from transcript_date
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($transcript) {
            if ($transcript->transcript_date) {
                $date = Carbon::parse($transcript->transcript_date);
                $transcript->year = $date->year;
                $transcript->month = $date->month;
            }

            // Auto-extract text and calculate counts if HTML is provided
            if ($transcript->content_html && !$transcript->content_text) {
                $transcript->content_text = $transcript->extractTextFromHtml();
            }

            if ($transcript->content_text) {
                $transcript->word_count = $transcript->calculateWordCount();
                $transcript->character_count = $transcript->calculateCharacterCount();
            }
        });
    }

    /**
     * Scope to filter by year
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to filter by month
     */
    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope to filter by committee
     */
    public function scopeByCommittee($query, $committeeId)
    {
        return $query->where('committee_id', $committeeId);
    }

    /**
     * Scope to search in content
     */
    public function scopeSearchContent($query, $search)
    {
        return $query->where('content_text', 'LIKE', '%' . $search . '%');
    }

    /**
     * Check if this transcript has been analyzed
     */
    public function hasBeenAnalyzed(): bool
    {
        return $this->billAnalyses()->exists();
    }

    /**
     * Get high-confidence bill analyses
     */
    public function highConfidenceAnalyses()
    {
        return $this->billAnalyses()->highConfidence();
    }

    /**
     * Get count of bill discussions found in this transcript
     */
    public function getBillDiscussionsCountAttribute(): int
    {
        return $this->billAnalyses()->count();
    }
}
