<?php

namespace App\Notifications;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Notifications\Notification;

/**
 * Tells a member that a moderator has reviewed their report.
 */
class ReportReviewed extends Notification
{
    public function __construct(public Report $report) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->report->status === ReportStatus::Resolved
                ? 'A moderator reviewed your report and took action. Thank you.'
                : 'A moderator reviewed your report and found no rule was broken.',
            'url' => route('notifications.index', absolute: false),
        ];
    }
}
