<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can see the list of users in the admin area.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    /**
     * Determine whether the user can change another user's role.
     *
     * Nobody may change their own role: that stops a user promoting
     * themselves and stops the last admin locking themselves out.
     */
    public function updateRole(User $user, User $target): bool
    {
        return $user->can('users.manage') && $user->isNot($target);
    }

    /**
     * Determine whether the user can delete another user's account.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->can('users.manage') && $user->isNot($target);
    }
}
