<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the signed-in member's own recipes, whatever their status.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'recipes' => Recipe::query()
                ->whereBelongsTo($request->user())
                ->with(['user', 'category'])
                ->latest('updated_at')
                ->paginate(10)
                ->through(fn (Recipe $recipe) => $recipe->toCard()),
        ]);
    }
}
