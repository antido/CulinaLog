<?php

namespace App\Http\Controllers\Moderation;

use App\Enums\CommentStatus;
use App\Enums\ForumStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\ModerationLog;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the moderator dashboard: what is waiting, the newest reports,
     * and what moderators did recently.
     *
     * Report details are only loaded for people who may review reports.
     */
    public function __invoke(Request $request): Response
    {
        $canReviewReports = $request->user()->can('reports.review');

        return Inertia::render('Moderation/Dashboard', [
            'stats' => [
                [
                    'label' => 'Pending comments',
                    'value' => Comment::where('status', CommentStatus::Pending)->count(),
                    'href' => route('moderation.pending', absolute: false),
                ],
                [
                    'label' => 'Pending forum replies',
                    'value' => ForumReply::where('status', ForumStatus::Pending)->count(),
                    'href' => route('moderation.pending', absolute: false),
                ],
                [
                    'label' => 'Reported content',
                    'value' => Report::where('status', ReportStatus::Pending)->count(),
                    'href' => $canReviewReports ? route('moderation.reports.index', absolute: false) : null,
                ],
            ],
            'recentReports' => $canReviewReports
                ? Report::query()->with(['reporter', 'reportable'])->latest()->limit(5)->get()
                    ->map(fn (Report $report) => [
                        'id' => $report->id,
                        'title' => $report->reason->label().': '.($report->target()['excerpt'] ?? 'deleted content'),
                        'detail' => $report->status->label().', reported by '.($report->reporter?->name ?? 'a guest'),
                        'when' => $report->created_at->diffForHumans(),
                        'url' => route('moderation.reports.index', ['status' => $report->status->value], absolute: false),
                    ])
                : [],
            'activity' => ModerationLog::query()->with('user')->latest()->limit(10)->get()
                ->map(fn (ModerationLog $entry) => [
                    'id' => $entry->id,
                    'title' => "{$entry->user->name} {$entry->action} {$entry->subject_label}",
                    'when' => $entry->created_at->diffForHumans(),
                ]),
        ]);
    }
}
