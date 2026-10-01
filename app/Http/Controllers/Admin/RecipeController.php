<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RecipeStatus;
use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    /**
     * List every recipe, whatever its status, with search and a status filter.
     * Editing and deleting use the normal recipe pages, which admins may open.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));
        $status = RecipeStatus::tryFrom((string) $request->query('status'));

        return Inertia::render('Admin/Recipes/Index', [
            'recipes' => Recipe::query()
                ->with(['user', 'category'])
                ->when($search !== '', fn ($query) => $query->whereContains('title', $search))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Recipe $recipe) => $recipe->toCard()),
            'filters' => ['search' => $search, 'status' => $status?->value ?? ''],
            'statuses' => array_map(
                fn (RecipeStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                RecipeStatus::cases(),
            ),
        ]);
    }
}
