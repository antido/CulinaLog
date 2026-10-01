<?php

use App\Http\Controllers\Moderation\CommentController;
use App\Http\Controllers\Moderation\DashboardController;
use App\Http\Controllers\Moderation\ForumReplyController;
use App\Http\Controllers\Moderation\ForumTopicController;
use App\Http\Controllers\Moderation\RecipeController;
use App\Http\Controllers\Moderation\ReportController;
use Illuminate\Support\Facades\Route;

/*
 * Moderation area. Open to anyone whose role has the listed permission,
 * which by default means moderators and admins. Controllers check the
 * matching policy again before changing anything.
 */
Route::middleware(['auth', 'verified'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::middleware('permission:dashboard.moderator')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        // The queue of guest comments and guest forum replies awaiting approval.
        Route::get('pending', [CommentController::class, 'index'])->name('pending');
    });

    Route::middleware('permission:reports.review')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::patch('reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    });

    Route::middleware('permission:recipes.moderate')->group(function () {
        Route::patch('recipes/{recipe}/hide', [RecipeController::class, 'hide'])->name('recipes.hide');
        Route::patch('recipes/{recipe}/restore', [RecipeController::class, 'restore'])->name('recipes.restore');
    });

    Route::patch('comments/{comment}', [CommentController::class, 'update'])
        ->middleware('permission:comments.moderate')
        ->name('comments.update');

    Route::middleware('permission:forum.moderate')->group(function () {
        Route::patch('forum/topics/{topic}', [ForumTopicController::class, 'update'])->name('forum.topics.update');
        Route::patch('forum/replies/{reply}', [ForumReplyController::class, 'update'])->name('forum.replies.update');
    });
});
