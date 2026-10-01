<?php

namespace App\Models;

use App\Enums\ForumStatus;
use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\ForumTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A forum discussion.
 *
 * Only the fields the topic form may change are fillable. The author, slug,
 * status, pinned and locked flags and the view count are set by server code.
 */
#[Fillable(['category_id', 'title', 'body'])]
class ForumTopic extends Model
{
    /** @use HasFactory<ForumTopicFactory> */
    use HasFactory, HasUniqueSlug;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ForumCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    /**
     * @return HasMany<ForumReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'topic_id');
    }

    /**
     * The replies everyone may see. Used for reply counts.
     *
     * @return HasMany<ForumReply, $this>
     */
    public function visibleReplies(): HasMany
    {
        return $this->replies()->where('status', ForumStatus::Visible);
    }

    /**
     * Only topics that everyone may see.
     *
     * @param  Builder<ForumTopic>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('forum_topics.status', ForumStatus::Visible);
    }

    /**
     * Pinned topics first, then the most recently active.
     *
     * @param  Builder<ForumTopic>  $query
     */
    public function scopeListingOrder(Builder $query): void
    {
        $query->orderByDesc('is_pinned')->orderByDesc('last_activity_at');
    }

    public function isVisible(): bool
    {
        return $this->status === ForumStatus::Visible;
    }

    /**
     * The details shown for a topic in a list. Load `user` and `category`
     * and count `visibleReplies` first to avoid extra queries.
     *
     * @return array<string, mixed>
     */
    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => Str::limit($this->body, 160),
            'status' => $this->status->value,
            'is_pinned' => $this->is_pinned,
            'is_locked' => $this->is_locked,
            'views' => $this->views,
            'replies_count' => $this->visible_replies_count ?? 0,
            'last_activity' => $this->last_activity_at?->diffForHumans(),
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
            'status' => ForumStatus::class,
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }
}
