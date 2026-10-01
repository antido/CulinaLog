<?php

namespace App\Enums;

enum CommentStatus: string
{
    /** Everyone can see it. */
    case Visible = 'visible';

    /** Waiting for a moderator to approve it. */
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
