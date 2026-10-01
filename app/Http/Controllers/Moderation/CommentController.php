<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\CommentStatus;
use App\Enums\ForumStatus;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ForumReply;
use App\Services\ActivityNotifier;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    /**
     * The approval queue: guest comments and guest forum replies that
     * are waiting for a moderator.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Moderation/Pending', [
            'comments' => $user->can('comments.moderate')
                ? Comment::query()
                    ->where('status', CommentStatus::Pending)
                    ->with('recipe')
                    ->oldest()
                    ->limit(50)
                    ->get()
                    ->map(fn (Comment $comment) => [
                        'id' => $comment->id,
                        'body' => Str::limit($comment->body, 500),
                        'author_name' => $comment->authorName(),
                        'posted' => $comment->created_at->diffForHumans(),
                        'where' => $comment->recipe->title,
                        'url' => route('recipes.show', $comment->recipe, absolute: false),
                    ])
                : [],
            'replies' => $user->can('forum.moderate')
                ? ForumReply::query()
                    ->where('status', ForumStatus::Pending)
                    ->with('topic')
                    ->oldest()
                    ->limit(50)
                    ->get()
                    ->map(fn (ForumReply $reply) => [
                        'id' => $reply->id,
                        'body' => Str::limit($reply->body, 500),
                        'author_name' => $reply->authorName(),
                        'posted' => $reply->created_at->diffForHumans(),
                        'where' => $reply->topic->title,
                        'url' => route('forum.topics.show', $reply->topic, absolute: false),
                    ])
                : [],
        ]);
    }

    /**
     * Approve, hide or restore a recipe comment.
     */
    public function update(
        Request $request,
        Comment $comment,
        ModerationService $moderation,
        ActivityNotifier $notifier,
    ): RedirectResponse {
        $this->authorize('moderate', $comment);

        $validated = $request->validate([
            'status' => ['required', Rule::in([CommentStatus::Visible->value, CommentStatus::Hidden->value])],
        ]);

        $wasPending = $comment->status === CommentStatus::Pending;

        if ($validated['status'] === CommentStatus::Hidden->value) {
            $moderation->hide($comment, $request->user());
        } else {
            $moderation->restore($comment, $request->user());
        }

        // A comment that was waiting for approval is "posted" now.
        if ($wasPending && $comment->status === CommentStatus::Visible) {
            $notifier->commentPosted($comment);
        }

        return back()->with('success', $comment->status === CommentStatus::Visible
            ? 'The comment is now visible.'
            : 'The comment is now hidden.');
    }
}
