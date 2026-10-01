<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommentStatus;
use App\Enums\RecipeStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\ForumTopic;
use App\Models\Recipe;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard: site totals and the latest activity.
     */
    public function __invoke(): Response
    {
        // One query counts recipes in every status, instead of one query each.
        $recipes = Recipe::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as published', [RecipeStatus::Published->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as drafts', [RecipeStatus::Draft->value])
            ->first();

        $comments = Comment::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as pending', [CommentStatus::Pending->value])
            ->first();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                ['label' => 'Total users', 'value' => User::count(), 'href' => route('admin.users.index', absolute: false)],
                ['label' => 'Total recipes', 'value' => (int) $recipes->total, 'href' => route('admin.recipes.index', absolute: false)],
                ['label' => 'Published recipes', 'value' => (int) $recipes->published],
                ['label' => 'Draft recipes', 'value' => (int) $recipes->drafts],
                ['label' => 'Comments', 'value' => (int) $comments->total],
                ['label' => 'Pending comments', 'value' => (int) $comments->pending, 'href' => route('moderation.pending', absolute: false)],
                ['label' => 'Forum topics', 'value' => ForumTopic::count()],
                [
                    'label' => 'Pending reports',
                    'value' => Report::where('status', ReportStatus::Pending)->count(),
                    'href' => route('moderation.reports.index', absolute: false),
                ],
            ],
            'recentRecipes' => Recipe::query()->with('user')->latest('id')->limit(5)->get()
                ->map(fn (Recipe $recipe) => [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'detail' => "{$recipe->status->label()}, by {$recipe->user->name}",
                    'when' => $recipe->created_at->diffForHumans(),
                    'url' => route('recipes.show', $recipe, absolute: false),
                ]),
            'recentComments' => Comment::query()->with(['user', 'recipe'])->latest('id')->limit(5)->get()
                ->map(fn (Comment $comment) => [
                    'id' => $comment->id,
                    'title' => Str::limit($comment->body, 80),
                    'detail' => "{$comment->status->label()}, by {$comment->authorName()} on \"{$comment->recipe->title}\"",
                    'when' => $comment->created_at->diffForHumans(),
                    'url' => route('recipes.show', $comment->recipe, absolute: false),
                ]),
            'recentUsers' => User::query()->with('roles:id,name')->latest('id')->limit(5)->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'title' => $user->name,
                    'detail' => '@'.$user->username.', '.($user->roles->first()?->name ?? 'no role'),
                    'when' => $user->created_at->diffForHumans(),
                    'url' => route('users.show', $user, absolute: false),
                ]),
            'recentTopics' => ForumTopic::query()->with('user')->latest('id')->limit(5)->get()
                ->map(fn (ForumTopic $topic) => [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'detail' => "{$topic->status->label()}, by {$topic->user->name}",
                    'when' => $topic->created_at->diffForHumans(),
                    'url' => route('forum.topics.show', $topic, absolute: false),
                ]),
        ]);
    }
}
