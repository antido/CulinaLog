<?php

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * A complaint about a piece of content.
 *
 * Only the reason and description come from the form. Who reported, what
 * was reported, the status and the reviewer are set by server code.
 */
#[Fillable(['reason', 'description'])]
#[Hidden(['guest_token_hash'])]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    /**
     * The kinds of content that can be reported. The form sends the short
     * name on the left; the class is looked up here, so a request can never
     * make the application load an arbitrary class.
     *
     * @var array<string, class-string<Model>>
     */
    public const REPORTABLE_TYPES = [
        'recipe' => Recipe::class,
        'comment' => Comment::class,
        'forum_topic' => ForumTopic::class,
        'forum_reply' => ForumReply::class,
    ];

    /**
     * The reported recipe, comment, forum topic or forum reply.
     * Null if it has since been deleted.
     *
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The member who made the report. Null for guests.
     *
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The moderator who reviewed the report.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * A description of the reported content for the moderation screen:
     * what kind of thing it is, a short excerpt, where to see it and
     * whether it is currently hidden. Null if the content was deleted.
     *
     * @return array{type: string, excerpt: string, url: string, is_hidden: bool}|null
     */
    public function target(): ?array
    {
        $content = $this->reportable;

        return match (true) {
            $content instanceof Recipe => [
                'type' => 'Recipe',
                'excerpt' => $content->title,
                'url' => route('recipes.show', $content, absolute: false),
                'is_hidden' => $content->is_hidden,
            ],
            $content instanceof Comment => [
                'type' => 'Comment',
                'excerpt' => Str::limit($content->body, 200),
                'url' => route('recipes.show', $content->recipe, absolute: false),
                'is_hidden' => $content->status->value === 'hidden',
            ],
            $content instanceof ForumTopic => [
                'type' => 'Forum topic',
                'excerpt' => $content->title,
                'url' => route('forum.topics.show', $content, absolute: false),
                'is_hidden' => $content->status->value === 'hidden',
            ],
            $content instanceof ForumReply => [
                'type' => 'Forum reply',
                'excerpt' => Str::limit($content->body, 200),
                'url' => route('forum.topics.show', $content->topic, absolute: false),
                'is_hidden' => $content->status->value === 'hidden',
            ],
            default => null,
        };
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }
}
