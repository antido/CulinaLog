<?php

namespace App\Policies;

use App\Models\ForumTopic;
use App\Models\User;

class ForumTopicPolicy
{
    /**
     * Determine whether the user can view the topic.
     * Hidden topics are for their author and for moderators only.
     */
    public function view(?User $user, ForumTopic $topic): bool
    {
        if ($topic->isVisible()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->id === $topic->user_id || $user->can('forum.moderate');
    }

    /**
     * Determine whether the user can start topics.
     */
    public function create(User $user): bool
    {
        return $user->can('forum.create');
    }

    /**
     * Determine whether the user can edit the topic: its author while it is
     * open and visible, or someone who manages the forum.
     */
    public function update(User $user, ForumTopic $topic): bool
    {
        if ($user->can('forum.manage')) {
            return true;
        }

        return $user->id === $topic->user_id
            && $user->can('forum.create')
            && $topic->isVisible()
            && ! $topic->is_locked;
    }

    /**
     * Determine whether the user can delete the topic.
     */
    public function delete(User $user, ForumTopic $topic): bool
    {
        if ($user->can('forum.manage')) {
            return true;
        }

        return $user->id === $topic->user_id && $user->can('forum.create');
    }

    /**
     * Determine whether the user can pin, lock, hide or restore the topic.
     */
    public function moderate(User $user, ForumTopic $topic): bool
    {
        return $user->can('forum.moderate');
    }

    /**
     * Determine whether someone can reply. Guests (no user) may reply only
     * where config/community.php allows it. Nobody replies to a locked or
     * hidden topic, except moderators.
     */
    public function reply(?User $user, ForumTopic $topic): bool
    {
        if ($user?->can('forum.moderate')) {
            return true;
        }

        if (! $topic->isVisible() || $topic->is_locked) {
            return false;
        }

        return $user !== null
            ? $user->can('forum.create')
            : (bool) config('community.guest_forum_replies');
    }
}
