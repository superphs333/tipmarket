<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Tip;
use App\Models\User;

class TipPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(Role::tipManagementRoles());
    }

    /**
     * Determine whether the user can manage tips in the console.
     */
    public function manageInConsole(User $user): bool
    {
        return $user->hasAnyRole(Role::tipManagementRoles());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tip $tip): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tip $tip): bool
    {
        return $tip->user_id === $user->id
            || $user->hasAnyRole(Role::tipManagementRoles());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tip $tip): bool
    {
        return $tip->user_id === $user->id
            || $user->hasAnyRole(Role::tipManagementRoles());
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tip $tip): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tip $tip): bool
    {
        return false;
    }
}
