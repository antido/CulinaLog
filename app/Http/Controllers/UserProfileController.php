<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\User;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    /**
     * Show a member's public profile.
     *
     * The page receives a hand-picked list of fields. Email, password
     * and other private columns are never included. Counts and lists
     * only include content that everyone may see.
     */
    public function show(User $user, SeoService $seo): Response
    {
        return Inertia::render('Users/Show', [
            'profile' => [
                ...$user->summary(),
                'bio' => $user->bio,
                'joined' => $user->created_at->format('F Y'),
            ],
            'stats' => [
                ['label' => 'Recipes', 'value' => $user->recipes()->visible()->count()],
                ['label' => 'Comments', 'value' => $user->comments()->visible()->count()],
                ['label' => 'Forum topics', 'value' => $user->forumTopics()->visible()->count()],
                ['label' => 'Forum replies', 'value' => $user->forumReplies()->visible()->count()],
            ],
            'recipes' => $user->recipes()
                ->visible()
                ->with(['user', 'category'])
                ->latest('published_at')
                ->paginate(9)
                ->through(fn (Recipe $recipe) => $recipe->toCard()),
        ])->withViewData('meta', $seo->forPage(
            "{$user->name} (@{$user->username})",
            $user->bio ?? "Recipes and forum posts by {$user->name} on CulinaLog.",
            $user->avatar_url,
        ));
    }
}
