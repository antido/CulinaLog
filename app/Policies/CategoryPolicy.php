<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view the category.
     * Visitors (no user) may only see active categories.
     */
    public function view(?User $user, Category $category): bool
    {
        return $category->is_active || ($user?->can('categories.manage') ?? false);
    }

    /**
     * Determine whether the user can open the category list in the admin area.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('categories.manage');
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->can('categories.manage');
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->can('categories.manage');
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->can('categories.manage');
    }
}
