<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Notifications\Notification;

/**
 * Tells a member that someone replied to their comment.
 */
class CommentReplied extends Notification
{
    public function __construct(public Comment $reply) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->reply->authorName()} replied to your comment on \"{$this->reply->recipe->title}\".",
            'url' => route('recipes.show', $this->reply->recipe, absolute: false),
        ];
    }
}
