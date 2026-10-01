<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Pages everyone can open.
 */
Route::get('/', HomeController::class)->name('home');

Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('/users/{user:username}', [UserProfileController::class, 'show'])->name('users.show');

Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');

// Search runs several LIKE queries, so each visitor gets 60 searches a minute.
Route::get('/search', SearchController::class)->middleware('throttle:60,1')->name('search');

/*
 * Pages for signed-in members with a verified email address.
 * Each controller action also checks a policy.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // "create" must be registered before /recipes/{recipe} below.
    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes', [RecipeController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('recipes.store');
    Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
    Route::patch('/recipes/{recipe}/status', [RecipeController::class, 'updateStatus'])->name('recipes.status');
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');
});

Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');

/*
 * Comments and reactions. Guests may use these too, so there is no "auth"
 * middleware; the form requests decide who is allowed, and the throttle
 * middleware limits how often (see AppServiceProvider).
 */
Route::post('/recipes/{recipe}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:comments')
    ->name('comments.store');
Route::post('/recipes/{recipe}/reactions', [ReactionController::class, 'store'])
    ->middleware('throttle:reactions')
    ->name('reactions.store');
Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
    ->middleware('auth')
    ->name('comments.destroy');

/*
 * Reporting content to the moderators. Guests may report too.
 */
Route::post('/reports', [ReportController::class, 'store'])
    ->middleware('throttle:reports')
    ->name('reports.store');

/*
 * Account settings and notifications.
 */
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/forum.php';
require __DIR__.'/admin.php';
require __DIR__.'/moderation.php';
