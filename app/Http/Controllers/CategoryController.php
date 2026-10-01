<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Recipe;
use App\Services\SeoService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * List the categories visitors may browse.
     */
    public function index(): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => Category::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category) => $category->summary()),
        ]);
    }

    /**
     * Show one category.
     */
    public function show(Category $category, SeoService $seo): Response
    {
        // A disabled category answers "not found" rather than "forbidden",
        // so visitors cannot tell that it exists.
        abort_unless(Gate::allows('view', $category), 404);

        return Inertia::render('Categories/Show', [
            'category' => $category->summary(),
            'recipes' => $category->recipes()
                ->visible()
                ->with(['user', 'category'])
                ->latest('published_at')
                ->paginate(12)
                ->through(fn (Recipe $recipe) => $recipe->toCard()),
        ])->withViewData('meta', $seo->forPage(
            "{$category->name} recipes",
            $category->description ?? "Recipes in the {$category->name} category on CulinaLog.",
            $category->image_url,
        ));
    }
}
