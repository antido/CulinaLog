<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    /**
     * Hide a recipe from the public.
     */
    public function hide(Request $request, Recipe $recipe, ModerationService $moderation): RedirectResponse
    {
        $this->authorize('moderate', $recipe);

        $moderation->hide($recipe, $request->user());

        return back()->with('success', "\"{$recipe->title}\" is now hidden from the public.");
    }

    /**
     * Make a hidden recipe public again.
     */
    public function restore(Request $request, Recipe $recipe, ModerationService $moderation): RedirectResponse
    {
        $this->authorize('moderate', $recipe);

        $moderation->restore($recipe, $request->user());

        return back()->with('success', "\"{$recipe->title}\" is visible again.");
    }
}
