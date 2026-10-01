<?php

use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumReplyController;
use App\Http\Controllers\ForumTopicController;
use Illuminate\Support\Facades\Route;

/*
 * The community forum. Reading is open to everyone.
 */
Route::prefix('forum')->name('forum.')->group(function () {
    Route::get('/', [ForumController::class, 'index'])->name('index');
    Route::get('/c/{forumCategory}', [ForumController::class, 'category'])->name('categories.show');

    // Starting and editing topics needs a verified account.
    Route::middleware(['auth', 'verified'])->group(function () {
        // "create" must be registered before /topics/{topic} below.
        Route::get('/topics/create', [ForumTopicController::class, 'create'])->name('topics.create');
        Route::post('/topics', [ForumTopicController::class, 'store'])
            ->middleware('throttle:forum-posts')
            ->name('topics.store');
        Route::get('/topics/{topic}/edit', [ForumTopicController::class, 'edit'])->name('topics.edit');
        Route::put('/topics/{topic}', [ForumTopicController::class, 'update'])->name('topics.update');
        Route::delete('/topics/{topic}', [ForumTopicController::class, 'destroy'])->name('topics.destroy');
    });

    Route::get('/topics/{topic}', [ForumTopicController::class, 'show'])->name('topics.show');

    // Guests may reply where config/community.php allows it, so there is no
    // "auth" middleware here. StoreForumReplyRequest decides who is allowed.
    Route::post('/topics/{topic}/replies', [ForumReplyController::class, 'store'])
        ->middleware('throttle:forum-posts')
        ->name('replies.store');

    Route::delete('/replies/{reply}', [ForumReplyController::class, 'destroy'])
        ->middleware('auth')
        ->name('replies.destroy');
});
