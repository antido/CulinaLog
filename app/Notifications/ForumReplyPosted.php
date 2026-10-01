<?php

namespace App\Notifications;

use App\Models\ForumReply;
use Illuminate\Notifications\Notification;

/**
 * Tells a member about a new reply: either in a topic they started,
 * or as an answer to a reply they wrote.
 */
class ForumReplyPosted extends Notification
{
    /**
     * @param  bool  $answersTheirReply  True when the new reply answers the member's own reply.
     */
    public function __construct(public ForumReply $reply, public bool $answersTheirReply = false) {}

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
        $who = $this->reply->authorName();
        $title = $this->reply->topic->title;

        return [
            'message' => $this->answersTheirReply
                ? "{$who} answered your reply in \"{$title}\"."
                : "{$who} replied to your topic \"{$title}\".",
            'url' => route('forum.topics.show', $this->reply->topic, absolute: false),
        ];
    }
}
