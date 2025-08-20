<?php

namespace App\Policies;

use App\Models\ParliamentMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ParliamentMemberPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Allow viewing list
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ParliamentMember $parliamentMember): bool
    {
        return true; // Allow viewing individual records
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ParliamentMember $parliamentMember): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ParliamentMember $parliamentMember): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ParliamentMember $parliamentMember): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ParliamentMember $parliamentMember): bool
    {
        return false;
    }
}
