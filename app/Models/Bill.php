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
        'bill_date',
        'path',
        'committee_id'
    ];

    protected $casts = [
        'bill_date' => 'datetime',
    ];

    /**
     * Get the committee that handles this bill
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class, 'committee_id', 'committee_id');
    }
}
