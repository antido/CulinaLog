<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Recipe;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Show the home page: the newest recipes and the categories.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'recipes' => Recipe::query()
                ->visible()
                ->with(['user', 'category'])
                ->latest('published_at')
                ->limit(6)
                ->get()
                ->map(fn (Recipe $recipe) => $recipe->toCard()),
            'categories' => Category::query()
                ->active()
                ->orderBy('name')
                ->get(['name', 'slug']),
        ])->withViewData('meta', ['title' => 'Recipes from home cooks']);
    }
}
