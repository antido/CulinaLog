<?php

namespace App\Policies;

use App\Models\ForumReply;
use App\Models\User;

class ForumReplyPolicy
{
    /**
     * Determine whether the user can delete the reply: the member who
     * wrote it, or someone who manages the forum.
     */
    public function delete(User $user, ForumReply $reply): bool
    {
        if ($user->can('forum.manage')) {
            return true;
        }

        return $reply->user_id !== null && $reply->user_id === $user->id;
    }

    /**
     * Determine whether the user can approve, hide or restore the reply.
     */
    public function moderate(User $user, ForumReply $reply): bool
    {
        return $user->can('forum.moderate');
    }
}
