<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Instruction;
use App\Models\Recipe;
use Illuminate\Support\Str;

/**
 * Builds the search-engine and link-preview details for a page.
 *
 * Controllers pass the result to the page template with
 *   ->withViewData('meta', $seo->forRecipe($recipe))
 * and resources/views/app.blade.php prints the tags into <head>.
 * They are printed by the server (not by React) so that search engines
 * and link previews see them without running JavaScript.
 */
class SeoService
{
    /**
     * The tags for an ordinary page.
     *
     * @return array<string, mixed>
     */
    public function forPage(string $title, ?string $description = null, ?string $image = null): array
    {
        return [
            'title' => $title,
            'description' => $description !== null ? $this->excerpt($description) : null,
            'image' => $image !== null ? url($image) : null,
        ];
    }

    /**
     * The tags for a recipe page, including schema.org "Recipe" structured
     * data, which lets search engines show cooking time, servings and a photo.
     * Load the recipe's user, category, ingredients and instructions first.
     *
     * @return array<string, mixed>
     */
    public function forRecipe(Recipe $recipe): array
    {
        $image = $recipe->image_url !== null ? url($recipe->image_url) : null;

        return [
            'title' => "{$recipe->title} by {$recipe->user->name}",
            'description' => $this->excerpt($recipe->description),
            'image' => $image,
            'type' => 'article',
            // Drafts, archived and hidden recipes must not appear in search engines.
            'noindex' => ! $recipe->isVisible(),
            'jsonLd' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Recipe',
                'name' => $recipe->title,
                'description' => $this->excerpt($recipe->description, 300),
                'image' => $image,
                'author' => ['@type' => 'Person', 'name' => $recipe->user->name],
                'datePublished' => $recipe->published_at?->toDateString(),
                'prepTime' => $this->duration($recipe->prep_time),
                'cookTime' => $this->duration($recipe->cook_time),
                'totalTime' => $this->duration($recipe->prep_time + $recipe->cook_time),
                'recipeYield' => "{$recipe->servings} servings",
                'recipeCategory' => $recipe->category->name,
                'recipeIngredient' => $recipe->ingredients
                    ->map(fn (Ingredient $ingredient) => trim(
                        implode(' ', array_filter([$ingredient->displayQuantity(), $ingredient->unit, $ingredient->name]))
                    ))
                    ->all(),
                'recipeInstructions' => $recipe->instructions
                    ->map(fn (Instruction $step) => ['@type' => 'HowToStep', 'text' => $step->instruction])
                    ->all(),
            ]),
        ];
    }

    /**
     * One line of plain text, short enough for a search result.
     */
    private function excerpt(string $text, int $length = 160): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/', ' ', $text)), $length);
    }

    /**
     * Minutes as an ISO 8601 duration: 75 becomes "PT1H15M".
     */
    private function duration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return 'PT'.($hours > 0 ? "{$hours}H" : '').($rest > 0 || $hours === 0 ? "{$rest}M" : '');
    }
}
