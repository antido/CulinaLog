<?php

namespace App\Models;

use App\Enums\ForumStatus;
use Database\Factories\ForumReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reply in a forum topic, written by a member or by a guest.
 * Only the text comes from the form; everything else is set by server code.
 */
#[Fillable(['body'])]
#[Hidden(['guest_token_hash'])]
class ForumReply extends Model
{
    /** @use HasFactory<ForumReplyFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ForumTopic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    /**
     * The member who wrote the reply. Null for a guest.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ForumReply, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'parent_id');
    }

    /**
     * The visible replies to this reply, oldest first.
     *
     * @return HasMany<ForumReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'parent_id')
            ->where('status', ForumStatus::Visible)
            ->oldest();
    }

    /**
     * Only replies that everyone may see.
     *
     * @param  Builder<ForumReply>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', ForumStatus::Visible);
    }

    /**
     * The name shown next to the reply. Guests are always "Anonymous".
     */
    public function authorName(): string
    {
        return $this->user_id !== null ? $this->user->name : 'Anonymous';
    }

    /**
     * The reply and its replies as plain data for the page, in the same
     * shape as Comment::toThread() so both use the same components.
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
                ? $this->replies->map(fn (ForumReply $reply) => $reply->toThread($viewer))->all()
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
            'status' => ForumStatus::class,
        ];
    }
}
