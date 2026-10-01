<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One action taken by a moderator, for example "hid a comment".
 * Entries are written by App\Services\ModerationService.
 */
class ModerationLog extends Model
{
    /**
     * The moderator who acted.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
