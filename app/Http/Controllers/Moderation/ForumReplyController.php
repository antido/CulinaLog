<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\ForumStatus;
use App\Http\Controllers\Controller;
use App\Models\ForumReply;
use App\Services\ActivityNotifier;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ForumReplyController extends Controller
{
    /**
     * Approve, hide or restore a forum reply.
     */
    public function update(
        Request $request,
        ForumReply $reply,
        ModerationService $moderation,
        ActivityNotifier $notifier,
    ): RedirectResponse {
        $this->authorize('moderate', $reply);

        $validated = $request->validate([
            'status' => ['required', Rule::in([ForumStatus::Visible->value, ForumStatus::Hidden->value])],
        ]);

        $wasPending = $reply->status === ForumStatus::Pending;

        if ($validated['status'] === ForumStatus::Hidden->value) {
            $moderation->hide($reply, $request->user());
        } else {
            $moderation->restore($reply, $request->user());
        }

        // An approved guest reply counts as new activity in its topic.
        if ($wasPending && $reply->status === ForumStatus::Visible) {
            $reply->topic->forceFill(['last_activity_at' => now()])->save();
            $notifier->forumReplyPosted($reply);
        }

        return back()->with('success', $reply->status === ForumStatus::Visible
            ? 'The reply is now visible.'
            : 'The reply is now hidden.');
    }
}
