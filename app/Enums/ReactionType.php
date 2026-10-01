<?php

namespace App\Enums;

enum ReactionType: string
{
    case Like = 'like';
    case Love = 'love';
    case Wow = 'wow';

    /**
     * The name shown to people.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
