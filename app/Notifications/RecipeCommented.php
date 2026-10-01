<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Notifications\Notification;

/**
 * Tells a recipe's author that someone commented on it.
 */
class RecipeCommented extends Notification
{
    public function __construct(public Comment $comment) {}

    /**
     * Notifications are stored in the database and shown inside the app.
     *
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
            'message' => "{$this->comment->authorName()} commented on your recipe \"{$this->comment->recipe->title}\".",
            'url' => route('recipes.show', $this->comment->recipe, absolute: false),
        ];
    }
}
