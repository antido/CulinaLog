<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine whether the user can delete the comment:
     * the member who wrote it, or someone who may delete any comment.
     *
     * Guest comments have no owner account, so only staff can delete them.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($user->can('comments.delete')) {
            return true;
        }

        return $comment->user_id !== null && $comment->user_id === $user->id;
    }

    /**
     * Determine whether the user can approve, hide or restore the comment.
     */
    public function moderate(User $user, Comment $comment): bool
    {
        return $user->can('comments.moderate');
    }
}
