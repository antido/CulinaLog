<?php

namespace App\Http\Controllers;

use App\Enums\ForumStatus;
use App\Http\Requests\StoreForumReplyRequest;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Services\ActivityNotifier;
use App\Services\GuestIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ForumReplyController extends Controller
{
    /**
     * Save a reply to a topic, from a member or (where allowed) a guest.
     */
    public function store(
        StoreForumReplyRequest $request,
        ForumTopic $topic,
        GuestIdentity $guests,
        ActivityNotifier $notifier,
    ): RedirectResponse {
        // Spam trap: see CommentController::store().
        if ($request->filled('website')) {
            return back()->with('success', 'Your reply was posted.');
        }

        $user = $request->user();
        $guestHash = $user === null ? $guests->hash($request) : null;
        $body = $request->validated('body');

        if ($this->isDuplicate($topic, $body, $user?->id, $guestHash)) {
            throw ValidationException::withMessages([
                'body' => 'You already posted this reply.',
            ]);
        }

        $reply = new ForumReply(['body' => $body]);
        $reply->topic_id = $topic->id;
        $reply->user_id = $user?->id;
        $reply->guest_token_hash = $guestHash;
        $this->attachToParent($reply, $request->validated('parent_id'));

        // Every guest reply waits for a moderator. Member replies appear at once.
        $reply->status = $user === null ? ForumStatus::Pending : ForumStatus::Visible;
        $reply->save();

        if ($reply->status === ForumStatus::Visible) {
            $topic->forceFill(['last_activity_at' => now()])->save();
            $notifier->forumReplyPosted($reply);
        }

        return back()->with('success', $user === null
            ? 'Thanks. Guest replies appear once a moderator has approved them.'
            : 'Your reply was posted.');
    }

    /**
     * Delete a reply and the replies under it.
     */
    public function destroy(ForumReply $reply): RedirectResponse
    {
        $this->authorize('delete', $reply);

        $reply->delete();

        return back()->with('success', 'The reply was deleted.');
    }

    /**
     * Whether the same person posted the same text in this topic a moment ago.
     */
    private function isDuplicate(ForumTopic $topic, string $body, ?int $userId, ?string $guestHash): bool
    {
        return ForumReply::query()
            ->where('topic_id', $topic->id)
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
     * Nest the reply under its parent, limited to the configured depth
     * in the same way as recipe comments.
     */
    private function attachToParent(ForumReply $reply, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = ForumReply::findOrFail($parentId);
        $deepestLevel = config('community.comment_depth') - 1;

        if ($parent->depth >= $deepestLevel) {
            $parent = $parent->parent;
        }

        $reply->parent_id = $parent->id;
        $reply->depth = $parent->depth + 1;
    }
}
