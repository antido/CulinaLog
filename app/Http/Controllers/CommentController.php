<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Recipe;
use App\Services\ActivityNotifier;
use App\Services\GuestIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    /**
     * Save a comment or a reply, from a member or a guest.
     */
    public function store(
        StoreCommentRequest $request,
        Recipe $recipe,
        GuestIdentity $guests,
        ActivityNotifier $notifier,
    ): RedirectResponse {
        // Spam trap. The "website" field is hidden from people, so only an
        // automated script fills it in. Answer as if it worked, save nothing.
        if ($request->filled('website')) {
            return back()->with('success', 'Your comment was posted.');
        }

        $user = $request->user();
        $guestHash = $user === null ? $guests->hash($request) : null;
        $body = $request->validated('body');

        if ($this->isDuplicate($recipe, $body, $user?->id, $guestHash)) {
            throw ValidationException::withMessages([
                'body' => 'You already posted this comment.',
            ]);
        }

        $comment = new Comment([
            'body' => $body,
            // Members always appear under their account name.
            'guest_name' => $user === null ? $request->validated('guest_name') : null,
        ]);
        $comment->recipe_id = $recipe->id;
        $comment->user_id = $user?->id;
        $comment->guest_token_hash = $guestHash;
        $this->attachToParent($comment, $request->validated('parent_id'));

        // Guest comments with links are the usual shape of spam,
        // so a moderator looks at them first.
        $needsReview = $user === null && $this->containsLink($body);
        $comment->status = $needsReview ? CommentStatus::Pending : CommentStatus::Visible;

        $comment->save();

        if (! $needsReview) {
            $notifier->commentPosted($comment);
        }

        return back()->with('success', $needsReview
            ? 'Your comment has a link in it, so a moderator will review it before it appears.'
            : 'Your comment was posted.');
    }

    /**
     * Delete a comment and its replies.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', 'The comment was deleted.');
    }

    /**
     * Whether the same person posted the same text on this recipe a moment ago.
     */
    private function isDuplicate(Recipe $recipe, string $body, ?int $userId, ?string $guestHash): bool
    {
        return Comment::query()
            ->where('recipe_id', $recipe->id)
            ->where('body', $body)
            ->where('created_at', '>=', now()->subMinutes(config('community.duplicate_window_minutes')))
            ->when(
                $userId !== null,
                fn ($query) => $query->where('user_id', $userId),
                fn ($query) => $query->where('guest_token_hash', $guestHash),
            )
            ->exists();
    }

    /**
     * Make the comment a reply when a parent was chosen.
     *
     * Replies are shown a limited number of levels deep. A reply to a comment
     * on the last level is attached to that comment's parent instead, so it
     * appears next to it rather than one level deeper.
     */
    private function attachToParent(Comment $comment, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = Comment::findOrFail($parentId);
        $deepestLevel = config('community.comment_depth') - 1;

        if ($parent->depth >= $deepestLevel) {
            $parent = $parent->parent;
        }

        $comment->parent_id = $parent->id;
        $comment->depth = $parent->depth + 1;
    }

    private function containsLink(string $text): bool
    {
        return preg_match('/(https?:\/\/|www\.)/i', $text) === 1;
    }
}
