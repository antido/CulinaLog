<?php

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case InappropriateContent = 'inappropriate';
    case CopyrightConcern = 'copyright';
    case MisleadingInformation = 'misleading';
    case Other = 'other';

    /**
     * The name shown to people.
     */
    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Harassment => 'Harassment',
            self::InappropriateContent => 'Inappropriate Content',
            self::CopyrightConcern => 'Copyright Concern',
            self::MisleadingInformation => 'Misleading Information',
            self::Other => 'Other',
        };
    }
}
