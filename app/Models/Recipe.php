<?php

namespace App\Models;

use App\Enums\ReactionType;
use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Services\ImageService;
use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Only the fields a recipe form may change are fillable.
 * user_id, slug, image, is_hidden and published_at are set by server code,
 * so a request can never choose the owner or un-hide a moderated recipe.
 */
#[Fillable(['category_id', 'title', 'description', 'prep_time', 'cook_time', 'servings', 'difficulty', 'status'])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * Remove the stored image file when a recipe is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(function (Recipe $recipe): void {
            app(ImageService::class)->delete($recipe->image);
        });
    }

    /**
     * The member who wrote the recipe.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The ingredients, in the order the author listed them.
     *
     * @return HasMany<Ingredient, $this>
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class)->orderBy('sort_order');
    }

    /**
     * The cooking steps, in order.
     *
     * @return HasMany<Instruction, $this>
     */
    public function instructions(): HasMany
    {
        return $this->hasMany(Instruction::class)->orderBy('step_number');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<Reaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * How many of each reaction the recipe has, for example
     * ['like' => 3, 'love' => 1, 'wow' => 0].
     *
     * @return array<string, int>
     */
    public function reactionCounts(): array
    {
        $counts = $this->reactions()
            ->toBase()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $result = [];

        foreach (ReactionType::cases() as $type) {
            $result[$type->value] = (int) ($counts[$type->value] ?? 0);
        }

        return $result;
    }

    /**
     * Only recipes that everyone may see: published and not hidden by a moderator.
     *
     * @param  Builder<Recipe>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', RecipeStatus::Published)->where('is_hidden', false);
    }

    /**
     * Whether everyone may see this recipe.
     */
    public function isVisible(): bool
    {
        return $this->status === RecipeStatus::Published && ! $this->is_hidden;
    }

    /**
     * The address of the recipe image, or null if it has none.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => ImageService::url($this->image));
    }

    /**
     * The details shown on a recipe card in a list.
     * Load the `user` and `category` relations first to avoid extra queries.
     *
     * @return array<string, mixed>
     */
    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => Str::limit($this->description, 140),
            'image_url' => $this->image_url,
            'total_time' => $this->prep_time + $this->cook_time,
            'servings' => $this->servings,
            'difficulty' => $this->difficulty->label(),
            'status' => $this->status->value,
            'is_hidden' => $this->is_hidden,
            'published' => $this->published_at?->toFormattedDateString(),
            'author' => $this->user->summary(),
            'category' => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ],
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty' => RecipeDifficulty::class,
            'status' => RecipeStatus::class,
            'is_hidden' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
