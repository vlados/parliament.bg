<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParliamentMember extends Model
{
    protected $fillable = [
        'member_id',
        'first_name',
        'middle_name',
        'last_name',
        'full_name',
        'electoral_district',
        'political_party',
        'profession',
        'email',
    ];

    protected $casts = [
        'member_id' => 'integer',
    ];

    /**
     * The committees that this parliament member belongs to
     */
    public function committees(): BelongsToMany
    {
        return $this->belongsToMany(Committee::class, 'committee_member')
                    ->withPivot(['position', 'date_from', 'date_to'])
                    ->withTimestamps();
    }

    /**
     * Get current committee memberships (where date_to is in the future)
     */
    public function currentCommittees(): BelongsToMany
    {
        return $this->committees()
                    ->wherePivot('date_to', '>', now());
    }
}
