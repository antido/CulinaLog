<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * For models that are found in the URL by a `slug` column,
 * for example /recipes/garlic-fried-rice.
 */
trait HasUniqueSlug
{
    /**
     * Turn a title into a slug nobody else in this table uses.
     * "Garlic Fried Rice" becomes "garlic-fried-rice", and if that is
     * taken, "garlic-fried-rice-2", "garlic-fried-rice-3" and so on.
     *
     * "create" is never used on its own, because /recipes/create
     * and similar addresses are the "new item" forms.
     */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 80, '') ?: 'item';
        $slug = $base;
        $number = 2;

        while ($slug === 'create' || static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$number}";
            $number++;
        }

        return $slug;
    }

    /**
     * Find the model by its slug when it appears in a route.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
