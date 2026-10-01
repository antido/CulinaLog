<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can see the list of reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('reports.review');
    }

    /**
     * Determine whether the user can resolve or dismiss the report.
     */
    public function review(User $user, Report $report): bool
    {
        return $user->can('reports.review');
    }
}
