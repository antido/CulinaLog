<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\User;
use App\Notifications\CommentReplied;
use App\Notifications\ForumReplyPosted;
use App\Notifications\RecipeCommented;
use App\Notifications\RecipeReacted;
use App\Notifications\ReportReviewed;

/**
 * Decides who should hear about new activity, and tells them.
 *
 * The rules that keep notifications useful:
 * - nobody is notified about their own action;
 * - one action produces at most one notification per person;
 * - content that is not visible yet (pending approval) notifies nobody.
 *   Call the method again once a moderator approves it.
 */
class ActivityNotifier
{
    /**
     * A visible comment or reply was posted on a recipe.
     */
    public function commentPosted(Comment $comment): void
    {
        $recipeAuthor = $comment->recipe->user;
        $parentAuthor = $comment->parent?->user;

        // A reply notifies the person it answers...
        if ($parentAuthor !== null && $this->isSomeoneElse($parentAuthor, $comment->user_id)) {
            $parentAuthor->notify(new CommentReplied($comment));
        }

        // ...and the recipe's author, unless they are that same person.
        if ($this->isSomeoneElse($recipeAuthor, $comment->user_id) && ! $recipeAuthor->is($parentAuthor)) {
            $recipeAuthor->notify(new RecipeCommented($comment));
        }
    }

    /**
     * Someone reacted to a recipe for the first time.
     * Do not call this when a person only switches between reactions.
     */
    public function reactionAdded(Reaction $reaction): void
    {
        $recipeAuthor = $reaction->recipe->user;

        if ($this->isSomeoneElse($recipeAuthor, $reaction->user_id)) {
            $recipeAuthor->notify(new RecipeReacted($reaction));
        }
    }

    /**
     * A visible reply was posted in a forum topic.
     */
    public function forumReplyPosted(ForumReply $reply): void
    {
        $topicAuthor = $reply->topic->user;
        $parentAuthor = $reply->parent?->user;

        if ($parentAuthor !== null && $this->isSomeoneElse($parentAuthor, $reply->user_id)) {
            $parentAuthor->notify(new ForumReplyPosted($reply, answersTheirReply: true));
        }

        if ($this->isSomeoneElse($topicAuthor, $reply->user_id) && ! $topicAuthor->is($parentAuthor)) {
            $topicAuthor->notify(new ForumReplyPosted($reply));
        }
    }

    /**
     * A moderator finished reviewing a report. Guests cannot be notified.
     */
    public function reportReviewed(Report $report): void
    {
        $report->reporter?->notify(new ReportReviewed($report));
    }

    /**
     * Whether $recipient is a different person from the one who acted.
     * $actorId is null when a guest acted.
     */
    private function isSomeoneElse(User $recipient, ?int $actorId): bool
    {
        return $recipient->id !== $actorId;
    }
}
