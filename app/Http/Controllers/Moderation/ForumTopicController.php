<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\UpdateForumTopicRequest;
use App\Models\ForumTopic;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;

class ForumTopicController extends Controller
{
    /**
     * Pin or unpin, lock or unlock, hide or restore a topic.
     */
    public function update(
        UpdateForumTopicRequest $request,
        ForumTopic $topic,
        ModerationService $moderation,
    ): RedirectResponse {
        $changes = $request->validated();
        $moderator = $request->user();

        if (array_key_exists('is_pinned', $changes)) {
            $topic->is_pinned = $changes['is_pinned'];
            $topic->save();
            $moderation->log($moderator, $topic->is_pinned ? 'pinned' : 'unpinned', $topic);
        }

        if (array_key_exists('is_locked', $changes)) {
            $topic->is_locked = $changes['is_locked'];
            $topic->save();
            $moderation->log($moderator, $topic->is_locked ? 'locked' : 'unlocked', $topic);
        }

        if (array_key_exists('status', $changes)) {
            if ($changes['status'] === 'hidden') {
                $moderation->hide($topic, $moderator);
            } else {
                $moderation->restore($topic, $moderator);
            }
        }

        return back()->with('success', 'The topic was updated.');
    }
}
