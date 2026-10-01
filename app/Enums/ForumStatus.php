<?php

namespace App\Enums;

/**
 * The moderation state of a forum topic or reply.
 */
enum ForumStatus: string
{
    /** Everyone can see it. */
    case Visible = 'visible';

    /** A guest reply waiting for a moderator to approve it. */
    case Pending = 'pending';

    /** Hidden by a moderator. */
    case Hidden = 'hidden';

    /**
     * The name shown to people.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
