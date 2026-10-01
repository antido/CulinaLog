<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A comment on a recipe, written by a member or by a guest.
 *
 * Only the text and the guest's display name come from the form. The author,
 * recipe, parent, depth, status and guest hash are all set by server code.
 */
#[Fillable(['body', 'guest_name'])]
#[Hidden(['guest_token_hash'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * The member who wrote the comment. Null for a guest.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The comment this one replies to, if any.
     *
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * The visible replies to this comment, oldest first.
     *
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->where('status', CommentStatus::Visible)
            ->oldest();
    }

    /**
     * Only comments that everyone may see.
     *
     * @param  Builder<Comment>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', CommentStatus::Visible);
    }

    /**
     * The name shown next to the comment. Guests appear under the name
     * they typed, or as "Anonymous".
     */
    public function authorName(): string
    {
        if ($this->user_id !== null) {
            return $this->user->name;
        }

        return $this->guest_name ?: 'Anonymous';
    }

    /**
     * The comment and its replies as plain data for the page.
     * Load `user` and `replies` (as deep as needed) first to avoid extra queries.
     *
     * The guest token hash is never included.
     *
     * @return array<string, mixed>
     */
    public function toThread(?User $viewer): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'author_name' => $this->authorName(),
            'author' => $this->user?->summary(),
            'is_guest' => $this->user_id === null,
            'posted' => $this->created_at->diffForHumans(),
            'posted_at' => $this->created_at->toIso8601String(),
            'depth' => $this->depth,
            'can_delete' => $viewer?->can('delete', $this) ?? false,
            'replies' => $this->relationLoaded('replies')
                ? $this->replies->map(fn (Comment $reply) => $reply->toThread($viewer))->all()
                : [],
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
            'status' => CommentStatus::class,
        ];
    }
}
