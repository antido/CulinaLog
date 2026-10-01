<?php

namespace App\Policies;

use App\Models\ForumCategory;
use App\Models\User;

class ForumCategoryPolicy
{
    /**
     * Determine whether the user can view the forum category.
     */
    public function view(?User $user, ForumCategory $category): bool
    {
        return $category->is_active || ($user?->can('forum.manage') ?? false);
    }

    /**
     * Determine whether the user can create, edit and delete forum categories.
     */
    public function manage(User $user): bool
    {
        return $user->can('forum.manage');
    }
}
