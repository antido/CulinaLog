<?php

namespace App\Enums;

enum RecipeStatus: string
{
    /** Only the author can see it. */
    case Draft = 'draft';

    /** Everyone can see it. */
    case Published = 'published';

    /** Taken down by the author; only the author can see it. */
    case Archived = 'archived';

    /**
     * The name shown to people.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
