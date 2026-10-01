<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    /**
     * Determine whether the user can view the recipe.
     *
     * Visitors (no user) see published recipes that are not hidden.
     * Drafts, archived and hidden recipes are for the author and staff only.
     */
    public function view(?User $user, Recipe $recipe): bool
    {
        if ($recipe->isVisible()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->id === $recipe->user_id
            || $user->can('recipes.moderate')
            || $user->can('recipes.manage');
    }

    /**
     * Determine whether the user can create recipes.
     */
    public function create(User $user): bool
    {
        return $user->can('recipes.create');
    }

    /**
     * Determine whether the user can update the recipe:
     * its author, or someone who may manage every recipe.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        if ($user->can('recipes.manage')) {
            return true;
        }

        return $user->id === $recipe->user_id && $user->can('recipes.create');
    }

    /**
     * Determine whether the user can delete the recipe.
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        return $this->update($user, $recipe);
    }

    /**
     * Determine whether the user can hide or restore the recipe as a moderator.
     */
    public function moderate(User $user, Recipe $recipe): bool
    {
        return $user->can('recipes.moderate');
    }
}
