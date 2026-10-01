<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Enums\ForumStatus;
use App\Enums\ReportStatus;
use App\Http\Requests\StoreReportRequest;
use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\Recipe;
use App\Models\Report;
use App\Services\GuestIdentity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    /**
     * Report a recipe, comment, forum topic or forum reply to the moderators.
     */
    public function store(StoreReportRequest $request, GuestIdentity $guests): RedirectResponse
    {
        $content = $this->findPublicContent($request->validated('type'), (int) $request->validated('id'));

        $user = $request->user();
        $reporter = $user !== null
            ? ['user_id' => $user->id]
            : ['guest_token_hash' => $guests->hash($request)];

        $alreadyReported = Report::query()
            ->where('reportable_type', $content->getMorphClass())
            ->where('reportable_id', $content->getKey())
            ->where('status', ReportStatus::Pending)
            ->where($reporter)
            ->exists();

        if ($alreadyReported) {
            return back()->with('success', 'You have already reported this. A moderator will look at it.');
        }

        $report = new Report($request->safe()->only(['reason', 'description']));
        $report->forceFill($reporter);
        $report->reportable()->associate($content);
        $report->status = ReportStatus::Pending;
        $report->save();

        return back()->with('success', 'Thank you. A moderator will review your report.');
    }

    /**
     * Find the reported content. Answers "not found" unless it exists and
     * is something the public can see, so reports cannot be used to probe
     * for hidden or draft content.
     */
    private function findPublicContent(string $type, int $id): Model
    {
        $content = Report::REPORTABLE_TYPES[$type]::find($id);

        $isPublic = match (true) {
            $content instanceof Recipe => $content->isVisible(),
            $content instanceof Comment => $content->status === CommentStatus::Visible,
            $content instanceof ForumTopic => $content->isVisible(),
            $content instanceof ForumReply => $content->status === ForumStatus::Visible,
            default => false,
        };

        abort_unless($isPublic, 404);

        return $content;
    }
}
