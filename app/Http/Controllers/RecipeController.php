<?php

namespace App\Http\Controllers;

use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Ingredient;
use App\Models\Instruction;
use App\Models\Recipe;
use App\Services\GuestIdentity;
use App\Services\ImageService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RecipeController extends Controller
{
    /**
     * The validated form fields that are saved on the recipe itself.
     */
    private const RECIPE_FIELDS = [
        'category_id', 'title', 'description', 'prep_time',
        'cook_time', 'servings', 'difficulty', 'status',
    ];

    /**
     * List published recipes, newest first.
     */
    public function index(): Response
    {
        return Inertia::render('Recipes/Index', [
            'recipes' => Recipe::query()
                ->visible()
                ->with(['user', 'category'])
                ->latest('published_at')
                ->paginate(12)
                ->through(fn (Recipe $recipe) => $recipe->toCard()),
        ])->withViewData('meta', [
            'title' => 'Recipes',
            'description' => 'Browse every recipe the CulinaLog community has published, newest first.',
        ]);
    }

    /**
     * Show the form for a new recipe.
     */
    public function create(): Response
    {
        $this->authorize('create', Recipe::class);

        return Inertia::render('Recipes/Create', $this->formOptions());
    }

    /**
     * Save a new recipe. The owner is always the signed-in user.
     */
    public function store(StoreRecipeRequest $request, ImageService $images): RedirectResponse
    {
        $recipe = new Recipe($request->safe()->only(self::RECIPE_FIELDS));
        $recipe->user_id = $request->user()->id;
        $recipe->slug = Recipe::uniqueSlug($recipe->title);
        $this->setPublishedDate($recipe);

        if ($request->hasFile('image')) {
            $recipe->image = $images->store($request->file('image'), 'recipes');
        }

        try {
            $this->saveWithParts($recipe, $request->validated('ingredients'), $request->validated('instructions'));
        } catch (Throwable $exception) {
            // Nothing was saved, so do not leave the uploaded image behind.
            $images->delete($recipe->image);

            throw $exception;
        }

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', $this->savedMessage($recipe));
    }

    /**
     * Show one recipe.
     */
    public function show(Request $request, Recipe $recipe, GuestIdentity $guests, SeoService $seo): Response
    {
        // Drafts, archived and hidden recipes answer "not found" to people
        // who may not see them, so their existence is not revealed.
        abort_unless(Gate::allows('view', $recipe), 404);

        $recipe->load(['user', 'category', 'ingredients', 'instructions']);
        $user = $request->user();

        return Inertia::render('Recipes/Show', [
            'recipe' => [
                ...$recipe->toCard(),
                'description' => $recipe->description,
                'prep_time' => $recipe->prep_time,
                'cook_time' => $recipe->cook_time,
                'ingredients' => $recipe->ingredients->map(fn (Ingredient $ingredient) => [
                    'id' => $ingredient->id,
                    'name' => $ingredient->name,
                    'quantity' => $ingredient->displayQuantity(),
                    'unit' => $ingredient->unit,
                ]),
                'instructions' => $recipe->instructions->map(fn (Instruction $instruction) => [
                    'id' => $instruction->id,
                    'step_number' => $instruction->step_number,
                    'instruction' => $instruction->instruction,
                ]),
            ],
            'can' => [
                'update' => $user?->can('update', $recipe) ?? false,
                'delete' => $user?->can('delete', $recipe) ?? false,
                'moderate' => $user?->can('moderate', $recipe) ?? false,
                // Commenting and reacting only make sense on a public recipe.
                'comment' => $recipe->isVisible()
                    && ($user?->can('comments.create') ?? config('community.guest_comments')),
                'react' => $recipe->isVisible()
                    && ($user?->can('reactions.create') ?? config('community.guest_reactions')),
            ],
            'reactions' => [
                'counts' => $recipe->reactionCounts(),
                'mine' => $this->viewerReaction($request, $recipe, $guests),
            ],
            // Top-level comments are paginated; each brings its replies with it.
            'comments' => $recipe->comments()
                ->visible()
                ->whereNull('parent_id')
                ->with(['user', 'replies.user', 'replies.replies.user'])
                ->latest()
                ->paginate(10, pageName: 'comments_page')
                ->withQueryString()
                ->through(fn (Comment $comment) => $comment->toThread($user)),
            'commentCount' => $recipe->comments()->visible()->count(),
        ])->withViewData('meta', $seo->forRecipe($recipe));
    }

    /**
     * Show the form for editing a recipe.
     */
    public function edit(Recipe $recipe): Response
    {
        $this->authorize('update', $recipe);

        return Inertia::render('Recipes/Edit', [
            ...$this->formOptions($recipe),
            'recipe' => [
                'slug' => $recipe->slug,
                'title' => $recipe->title,
                'category_id' => $recipe->category_id,
                'description' => $recipe->description,
                'image_url' => $recipe->image_url,
                'prep_time' => $recipe->prep_time,
                'cook_time' => $recipe->cook_time,
                'servings' => $recipe->servings,
                'difficulty' => $recipe->difficulty->value,
                'status' => $recipe->status->value,
                'ingredients' => $recipe->ingredients->map(fn (Ingredient $ingredient) => [
                    'name' => $ingredient->name,
                    'quantity' => $ingredient->displayQuantity() ?? '',
                    'unit' => $ingredient->unit ?? '',
                ]),
                'instructions' => $recipe->instructions->map(fn (Instruction $instruction) => [
                    'instruction' => $instruction->instruction,
                ]),
            ],
        ]);
    }

    /**
     * Save changes to a recipe. The slug stays the same so links keep working.
     */
    public function update(UpdateRecipeRequest $request, Recipe $recipe, ImageService $images): RedirectResponse
    {
        $recipe->fill($request->safe()->only(self::RECIPE_FIELDS));
        $this->setPublishedDate($recipe);

        $oldImage = $recipe->image;
        $newImage = $request->hasFile('image')
            ? $images->store($request->file('image'), 'recipes')
            : null;

        if ($newImage !== null) {
            $recipe->image = $newImage;
        } elseif ($request->boolean('remove_image')) {
            $recipe->image = null;
        }

        try {
            $this->saveWithParts($recipe, $request->validated('ingredients'), $request->validated('instructions'));
        } catch (Throwable $exception) {
            // The recipe still points at its old image, so only remove the new upload.
            $images->delete($newImage);

            throw $exception;
        }

        // The old file is removed only after the change is safely saved.
        if ($recipe->image !== $oldImage) {
            $images->delete($oldImage);
        }

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', $this->savedMessage($recipe));
    }

    /**
     * Publish, archive or move a recipe back to draft from the dashboard.
     */
    public function updateStatus(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorize('update', $recipe);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(RecipeStatus::class)],
        ]);

        $recipe->status = $validated['status'];
        $this->setPublishedDate($recipe);
        $recipe->save();

        return back()->with('success', $this->savedMessage($recipe));
    }

    /**
     * Delete a recipe.
     */
    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorize('delete', $recipe);

        $recipe->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', "\"{$recipe->title}\" was deleted.");
    }

    /**
     * Which reaction the person viewing the page has given, if any.
     * Members are matched by account, guests by their cookie token.
     */
    private function viewerReaction(Request $request, Recipe $recipe, GuestIdentity $guests): ?string
    {
        $user = $request->user();
        $guestHash = $user === null ? $guests->existingHash($request) : null;

        if ($user === null && $guestHash === null) {
            return null;
        }

        return $recipe->reactions()
            ->when(
                $user !== null,
                fn ($query) => $query->where('user_id', $user->id),
                fn ($query) => $query->where('guest_token_hash', $guestHash),
            )
            ->first()
            ?->type
            ->value;
    }

    /**
     * Save the recipe with its ingredients and steps as one unit.
     *
     * The transaction means either everything is saved or nothing is:
     * a recipe can never end up with half of its ingredients.
     * The lists are replaced in full, numbered in the order they arrived.
     *
     * @param  list<array{name: string, quantity?: string|null, unit?: string|null}>  $ingredients
     * @param  list<array{instruction: string}>  $instructions
     */
    private function saveWithParts(Recipe $recipe, array $ingredients, array $instructions): void
    {
        DB::transaction(function () use ($recipe, $ingredients, $instructions): void {
            $recipe->save();

            $recipe->ingredients()->delete();
            foreach (array_values($ingredients) as $position => $ingredient) {
                $recipe->ingredients()->create([
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'] ?? null,
                    'unit' => $ingredient['unit'] ?? null,
                    'sort_order' => $position + 1,
                ]);
            }

            $recipe->instructions()->delete();
            foreach (array_values($instructions) as $position => $instruction) {
                $recipe->instructions()->create([
                    'step_number' => $position + 1,
                    'instruction' => $instruction['instruction'],
                ]);
            }
        });
    }

    /**
     * The choices the recipe form offers. When editing, the recipe's own
     * category is included even if it has been disabled.
     *
     * @return array<string, mixed>
     */
    private function formOptions(?Recipe $recipe = null): array
    {
        return [
            'categories' => Category::query()
                ->where('is_active', true)
                ->when($recipe, fn ($query) => $query->orWhere('id', $recipe->category_id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'difficulties' => array_map(
                fn (RecipeDifficulty $difficulty) => ['value' => $difficulty->value, 'label' => $difficulty->label()],
                RecipeDifficulty::cases(),
            ),
            'statuses' => array_map(
                fn (RecipeStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                RecipeStatus::cases(),
            ),
        ];
    }

    /**
     * Record when a recipe was first published.
     */
    private function setPublishedDate(Recipe $recipe): void
    {
        if ($recipe->status === RecipeStatus::Published && $recipe->published_at === null) {
            $recipe->published_at = now();
        }
    }

    private function savedMessage(Recipe $recipe): string
    {
        return match ($recipe->status) {
            RecipeStatus::Published => "\"{$recipe->title}\" is published.",
            RecipeStatus::Draft => "\"{$recipe->title}\" is saved as a draft. Only you can see it.",
            RecipeStatus::Archived => "\"{$recipe->title}\" is archived. Only you can see it.",
        };
    }
}
