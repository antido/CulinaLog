<?php

namespace App\Http\Controllers;

use App\Enums\ReactionType;
use App\Http\Requests\StoreReactionRequest;
use App\Models\Reaction;
use App\Models\Recipe;
use App\Services\ActivityNotifier;
use App\Services\GuestIdentity;
use Illuminate\Http\RedirectResponse;

class ReactionController extends Controller
{
    /**
     * React to a recipe. Each person has at most one reaction per recipe:
     * choosing the same one again removes it, choosing another replaces it.
     */
    public function store(
        StoreReactionRequest $request,
        Recipe $recipe,
        GuestIdentity $guests,
        ActivityNotifier $notifier,
    ): RedirectResponse {
        $type = ReactionType::from($request->validated('type'));
        $user = $request->user();

        // Who is reacting: a member (by id) or a guest (by token hash).
        $owner = $user !== null
            ? ['user_id' => $user->id]
            : ['guest_token_hash' => $guests->hash($request)];

        $reaction = Reaction::query()
            ->where('recipe_id', $recipe->id)
            ->where($owner)
            ->first();

        if ($reaction?->type === $type) {
            $reaction->delete();

            return back();
        }

        $isNew = $reaction === null;

        if ($isNew) {
            $reaction = new Reaction;
            $reaction->recipe_id = $recipe->id;
            $reaction->forceFill($owner);
        }

        $reaction->type = $type;
        $reaction->save();

        // Switching from one reaction to another does not notify the author again.
        if ($isNew) {
            $notifier->reactionAdded($reaction);
        }

        return back();
    }
}
