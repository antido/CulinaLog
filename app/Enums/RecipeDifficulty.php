<?php

namespace App\Enums;

enum RecipeDifficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    /**
     * The name shown to people.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
