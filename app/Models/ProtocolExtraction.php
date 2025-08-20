<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocolExtraction extends Model
{
    protected $fillable = [
        'transcript_id',
        'extraction_type',
        'extracted_data',
        'extraction_date',
        'metadata',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'metadata' => 'array',
        'extraction_date' => 'datetime',
    ];

    /**
     * Extraction types
     */
    const TYPE_BILL_DISCUSSIONS = 'bill_discussions';
    const TYPE_COMMITTEE_DECISIONS = 'committee_decisions';
    const TYPE_AMENDMENTS = 'amendments';
    const TYPE_SPEAKER_STATEMENTS = 'speaker_statements';

    /**
     * Get the transcript that owns this extraction
     */
    public function transcript(): BelongsTo
    {
        return $this->belongsTo(Transcript::class);
    }

    /**
     * Scope to filter by extraction type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('extraction_type', $type);
    }

    /**
     * Scope to get recent extractions
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('extraction_date', '>=', now()->subDays($days));
    }

    /**
     * Get bill discussions from extracted data
     */
    public function getBillDiscussions(): array
    {
        if ($this->extraction_type !== self::TYPE_BILL_DISCUSSIONS) {
            return [];
        }

        return $this->extracted_data['bills'] ?? [];
    }

    /**
     * Get committee decisions from extracted data
     */
    public function getCommitteeDecisions(): array
    {
        if ($this->extraction_type !== self::TYPE_COMMITTEE_DECISIONS) {
            return [];
        }

        return $this->extracted_data['decisions'] ?? [];
    }

    /**
     * Get amendments from extracted data
     */
    public function getAmendments(): array
    {
        if ($this->extraction_type !== self::TYPE_AMENDMENTS) {
            return [];
        }

        return $this->extracted_data['amendments'] ?? [];
    }

    /**
     * Get speaker statements from extracted data
     */
    public function getSpeakerStatements(): array
    {
        if ($this->extraction_type !== self::TYPE_SPEAKER_STATEMENTS) {
            return [];
        }

        return $this->extracted_data['speakers'] ?? [];
    }

    /**
     * Check if extraction contains voting results
     */
    public function hasVotingResults(): bool
    {
        $data = $this->extracted_data;
        
        // Check for voting in different structures
        if (isset($data['voting_results'])) {
            return true;
        }
        
        if (isset($data['bills']) && is_array($data['bills'])) {
            foreach ($data['bills'] as $bill) {
                if (isset($bill['voting_results'])) {
                    return true;
                }
            }
        }
        
        if (isset($data['decisions']) && is_array($data['decisions'])) {
            foreach ($data['decisions'] as $decision) {
                if (isset($decision['voting'])) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get all voting results from extraction
     */
    public function getVotingResults(): array
    {
        $results = [];
        $data = $this->extracted_data;
        
        // Direct voting results
        if (isset($data['voting_results'])) {
            $results[] = $data['voting_results'];
        }
        
        // Voting in bills
        if (isset($data['bills']) && is_array($data['bills'])) {
            foreach ($data['bills'] as $bill) {
                if (isset($bill['voting_results'])) {
                    $results[] = array_merge(
                        ['bill' => $bill['bill_number'] ?? 'Unknown'],
                        $bill['voting_results']
                    );
                }
            }
        }
        
        // Voting in decisions
        if (isset($data['decisions']) && is_array($data['decisions'])) {
            foreach ($data['decisions'] as $decision) {
                if (isset($decision['voting'])) {
                    $results[] = array_merge(
                        ['decision' => $decision['subject'] ?? 'Unknown'],
                        $decision['voting']
                    );
                }
            }
        }
        
        return $results;
    }

    /**
     * Get summary statistics of the extraction
     */
    public function getStatistics(): array
    {
        $stats = [
            'extraction_type' => $this->extraction_type,
            'extraction_date' => $this->extraction_date->toDateTimeString(),
        ];
        
        switch ($this->extraction_type) {
            case self::TYPE_BILL_DISCUSSIONS:
                $bills = $this->getBillDiscussions();
                $stats['total_bills'] = count($bills);
                $stats['bills_with_votes'] = collect($bills)->filter(function ($bill) {
                    return isset($bill['voting_results']);
                })->count();
                break;
                
            case self::TYPE_COMMITTEE_DECISIONS:
                $decisions = $this->getCommitteeDecisions();
                $stats['total_decisions'] = count($decisions);
                break;
                
            case self::TYPE_AMENDMENTS:
                $amendments = $this->getAmendments();
                $stats['total_amendments'] = count($amendments);
                $stats['accepted'] = collect($amendments)->where('status', 'accepted')->count();
                $stats['rejected'] = collect($amendments)->where('status', 'rejected')->count();
                break;
                
            case self::TYPE_SPEAKER_STATEMENTS:
                $speakers = $this->getSpeakerStatements();
                $stats['total_speakers'] = count($speakers);
                $stats['total_statements'] = collect($speakers)->sum(function ($speaker) {
                    return count($speaker['statements'] ?? []);
                });
                break;
        }
        
        return $stats;
    }
}