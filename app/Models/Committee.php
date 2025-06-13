<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Committee extends Model
{
    protected $fillable = [
        'committee_id',
        'committee_type_id',
        'name',
        'active_count',
        'date_from',
        'date_to',
        'email',
        'room',
        'phone',
        'rules',
    ];

    protected $casts = [
        'committee_id' => 'integer',
        'committee_type_id' => 'integer',
        'active_count' => 'integer',
        'date_from' => 'date',
        'date_to' => 'date',
    ];

    /**
     * The parliament members that belong to this committee
     */
    public function parliamentMembers(): BelongsToMany
    {
        return $this->belongsToMany(ParliamentMember::class, 'committee_member')
                    ->withPivot(['position', 'date_from', 'date_to'])
                    ->withTimestamps();
    }

    /**
     * Get current committee members (where date_to is in the future)
     */
    public function currentMembers(): BelongsToMany
    {
        return $this->parliamentMembers()
                    ->wherePivot('date_to', '>', now());
    }
}
