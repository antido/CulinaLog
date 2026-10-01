<?php

namespace App\Http\Controllers;

use App\Enums\RecipeDifficulty;
use App\Models\Category;
use App\Models\ForumTopic;
use App\Models\Recipe;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * The "ready in" choices, in minutes of preparation plus cooking.
     */
    private const TIME_LIMITS = [15, 30, 60, 120];

    private const SORTS = ['newest', 'oldest', 'popular'];

    /**
     * Search recipes (the default) or forum topics.
     *
     * Every filter value from the address bar is checked against a list of
     * allowed values; anything else is ignored. Search words are only ever
     * passed to the database as bound parameters (see whereContains in
     * AppServiceProvider), never joined into SQL.
     */
    public function __invoke(Request $request): Response
    {
        $filters = [
            'q' => mb_substr(trim((string) $request->query('q')), 0, 100),
            'type' => $request->query('type') === 'topics' ? 'topics' : 'recipes',
            'category' => (string) $request->query('category'),
            'difficulty' => RecipeDifficulty::tryFrom((string) $request->query('difficulty'))?->value ?? '',
            'max_time' => in_array((int) $request->query('max_time'), self::TIME_LIMITS, true)
                ? (int) $request->query('max_time')
                : '',
            'sort' => in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'newest',
        ];

        $categories = Category::query()->active()->orderBy('name')->get(['name', 'slug']);

        if (! $categories->contains('slug', $filters['category'])) {
            $filters['category'] = '';
        }

        return Inertia::render('Search/Index', [
            'filters' => $filters,
            'results' => $filters['type'] === 'topics'
                ? $this->searchTopics($filters)
                : $this->searchRecipes($filters),
            'categories' => $categories,
            'difficulties' => array_map(
                fn (RecipeDifficulty $difficulty) => ['value' => $difficulty->value, 'label' => $difficulty->label()],
                RecipeDifficulty::cases(),
            ),
            'timeLimits' => self::TIME_LIMITS,
        ])->withViewData('meta', [
            'title' => 'Search',
            // Search result pages are endless variations; keep them out of search engines.
            'noindex' => true,
        ]);
    }

    /**
     * Recipes whose title, description, ingredients, category or author
     * match the search words, narrowed by the chosen filters.
     *
     * @param  array<string, mixed>  $filters
     */
    private function searchRecipes(array $filters): LengthAwarePaginator
    {
        $search = $filters['q'];

        return Recipe::query()
            ->visible()
            // Loaded up front so the list does not run a query per recipe.
            ->with(['user', 'category'])
            ->withCount('reactions')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereContains('title', $search)
                        ->orWhereContains('description', $search)
                        ->orWhereHas('ingredients', fn ($query) => $query->whereContains('name', $search))
                        ->orWhereHas('category', fn ($query) => $query->whereContains('name', $search))
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->whereContains('name', $search)->orWhereContains('username', $search);
                        });
                });
            })
            ->when($filters['category'] !== '', function ($query) use ($filters): void {
                $query->whereHas('category', fn ($query) => $query->where('slug', $filters['category']));
            })
            ->when($filters['difficulty'] !== '', fn ($query) => $query->where('difficulty', $filters['difficulty']))
            ->when($filters['max_time'] !== '', function ($query) use ($filters): void {
                $query->whereRaw('prep_time + cook_time <= ?', [$filters['max_time']]);
            })
            ->when($filters['sort'] === 'popular', fn ($query) => $query->orderByDesc('reactions_count'))
            ->when(
                $filters['sort'] === 'oldest',
                fn ($query) => $query->oldest('published_at'),
                fn ($query) => $query->latest('published_at'),
            )
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Recipe $recipe) => $recipe->toCard());
    }

    /**
     * Forum topics whose title or text match the search words.
     *
     * @param  array<string, mixed>  $filters
     */
    private function searchTopics(array $filters): LengthAwarePaginator
    {
        $search = $filters['q'];

        return ForumTopic::query()
            ->visible()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->with(['user', 'category'])
            ->withCount('visibleReplies')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereContains('title', $search)->orWhereContains('body', $search);
                });
            })
            ->when($filters['sort'] === 'popular', fn ($query) => $query->orderByDesc('visible_replies_count'))
            ->when(
                $filters['sort'] === 'oldest',
                fn ($query) => $query->oldest(),
                fn ($query) => $query->latest(),
            )
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ForumTopic $topic) => $topic->toCard());
    }
}
