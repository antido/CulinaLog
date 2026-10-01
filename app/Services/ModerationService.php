<?php

namespace App\Services;

use App\Enums\CommentStatus;
use App\Enums\ForumStatus;
use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\ModerationLog;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Hides and restores content, and keeps a log of what moderators do.
 *
 * Callers must check the policy first; this class only does the work.
 */
class ModerationService
{
    /**
     * Hide a recipe, comment, forum topic or forum reply from the public.
     */
    public function hide(Model $content, User $moderator): void
    {
        $this->setHidden($content, true);
        $this->log($moderator, 'hid', $content);
    }

    /**
     * Make hidden (or pending) content visible.
     */
    public function restore(Model $content, User $moderator): void
    {
        $this->setHidden($content, false);
        $this->log($moderator, 'restored', $content);
    }

    /**
     * Record a moderator's action, for example log($user, 'pinned', $topic).
     */
    public function log(User $moderator, string $action, Model $subject): void
    {
        $entry = new ModerationLog;
        $entry->user_id = $moderator->id;
        $entry->action = $action;
        $entry->subject_type = $subject->getMorphClass();
        $entry->subject_id = $subject->getKey();
        $entry->subject_label = $this->label($subject);
        $entry->save();
    }

    /**
     * Each kind of content stores "hidden" in its own way.
     */
    private function setHidden(Model $content, bool $hidden): void
    {
        match (true) {
            $content instanceof Recipe => $content->is_hidden = $hidden,
            $content instanceof Comment => $content->status = $hidden ? CommentStatus::Hidden : CommentStatus::Visible,
            $content instanceof ForumTopic,
            $content instanceof ForumReply => $content->status = $hidden ? ForumStatus::Hidden : ForumStatus::Visible,
            default => throw new InvalidArgumentException('This content cannot be moderated.'),
        };

        $content->save();
    }

    /**
     * A short description of the content for the log, such as
     * 'Recipe "Garlic Fried Rice"' or 'Comment "Looks tasty..."'.
     */
    private function label(Model $subject): string
    {
        [$kind, $text] = match (true) {
            $subject instanceof Recipe => ['Recipe', $subject->title],
            $subject instanceof Comment => ['Comment', $subject->body],
            $subject instanceof ForumTopic => ['Forum topic', $subject->title],
            $subject instanceof ForumReply => ['Forum reply', $subject->body],
            default => ['Report', '#'.$subject->getKey()],
        };

        return Str::limit("{$kind} \"".Str::limit($text, 100).'"', 157);
    }
}
