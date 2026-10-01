<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\ReviewReportRequest;
use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\Report;
use App\Services\ActivityNotifier;
use App\Services\ModerationService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * List reports. Shows pending ones unless ?status=resolved or dismissed.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);

        $status = ReportStatus::tryFrom((string) $request->query('status')) ?? ReportStatus::Pending;

        $reports = Report::query()
            ->where('status', $status)
            // Load each reported item, plus the page it lives on, in a few queries.
            ->with(['reporter', 'reviewer', 'reportable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Comment::class => ['recipe'],
                    ForumReply::class => ['topic'],
                ]);
            }])
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Report $report) => [
                'id' => $report->id,
                'reason' => $report->reason->label(),
                'description' => $report->description,
                'status' => $report->status->value,
                'reported' => $report->created_at->diffForHumans(),
                'reporter' => $report->reporter?->name ?? 'A guest',
                'reviewer' => $report->reviewer?->name,
                'target' => $report->target(),
            ]);

        return Inertia::render('Moderation/Reports/Index', [
            'reports' => $reports,
            'status' => $status->value,
            'statuses' => array_map(
                fn (ReportStatus $status) => ['value' => $status->value, 'label' => $status->label()],
                ReportStatus::cases(),
            ),
        ]);
    }

    /**
     * Resolve or dismiss a report, optionally hiding the reported content.
     */
    public function update(
        ReviewReportRequest $request,
        Report $report,
        ModerationService $moderation,
        ActivityNotifier $notifier,
    ): RedirectResponse {
        $moderator = $request->user();
        $content = $report->reportable;

        if ($request->boolean('hide_content') && $content !== null) {
            // Reviewing reports does not by itself allow hiding every kind
            // of content, so the content's own policy is checked as well.
            Gate::authorize('moderate', $content);

            $moderation->hide($content, $moderator);
        }

        $report->status = $request->validated('status');
        $report->reviewed_by = $moderator->id;
        $report->reviewed_at = now();
        $report->save();

        $moderation->log($moderator, $report->status->value.' a report about', $content ?? $report);
        $notifier->reportReviewed($report);

        return back()->with('success', 'The report was marked as '.$report->status->value.'.');
    }
}
