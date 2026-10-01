<?php

namespace App\Enums;

enum ReportStatus: string
{
    /** Waiting for a moderator. */
    case Pending = 'pending';

    /** A moderator agreed and dealt with it. */
    case Resolved = 'resolved';

    /** A moderator found nothing wrong. */
    case Dismissed = 'dismissed';

    /**
     * The name shown to people.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
