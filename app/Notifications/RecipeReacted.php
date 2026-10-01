<?php

namespace App\Notifications;

use App\Models\Reaction;
use Illuminate\Notifications\Notification;

/**
 * Tells a recipe's author that someone reacted to it.
 */
class RecipeReacted extends Notification
{
    public function __construct(public Reaction $reaction) {}

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
        $who = $this->reaction->user?->name ?? 'A guest';
        $reaction = strtolower($this->reaction->type->label());

        return [
            'message' => "{$who} reacted \"{$reaction}\" to your recipe \"{$this->reaction->recipe->title}\".",
            'url' => route('recipes.show', $this->reaction->recipe, absolute: false),
        ];
    }
}
