<?php

namespace Tests\Feature;

use App\Enums\ReactionType;
use App\Models\Reaction;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    private const GUEST_TOKEN = 'cccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccccc';

    private const OTHER_GUEST_TOKEN = 'dddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd';

    public function test_member_can_react_to_a_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'love'])
            ->assertSessionHasNoErrors();

        $reaction = Reaction::first();

        $this->assertSame($user->id, $reaction->user_id);
        $this->assertNull($reaction->guest_token_hash);
        $this->assertSame(ReactionType::Love, $reaction->type);
    }

    public function test_guest_can_react_with_an_anonymous_token(): void
    {
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like'])
            ->assertSessionHasNoErrors()
            ->assertCookie('guest_token');

        $reaction = Reaction::first();

        $this->assertNull($reaction->user_id);
        $this->assertSame(64, strlen($reaction->guest_token_hash));
    }

    public function test_guest_reaction_stores_a_hash_of_the_token(): void
    {
        $recipe = Recipe::factory()->create();

        $this->withCookie('guest_token', self::GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'wow']);

        $this->assertSame(hash('sha256', self::GUEST_TOKEN), Reaction::first()->guest_token_hash);
    }

    public function test_a_forged_or_malformed_guest_cookie_is_replaced(): void
    {
        $recipe = Recipe::factory()->create();

        $this->withCookie('guest_token', 'short')
            ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like'])
            ->assertCookie('guest_token');

        $this->assertNotSame(hash('sha256', 'short'), Reaction::first()->guest_token_hash);
    }

    public function test_reacting_twice_with_the_same_type_removes_the_reaction(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like']);
        $this->actingAs($user)->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like']);

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_choosing_another_type_replaces_the_reaction(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like']);
        $this->actingAs($user)->post("/recipes/{$recipe->slug}/reactions", ['type' => 'wow']);

        $this->assertDatabaseCount('reactions', 1);
        $this->assertSame(ReactionType::Wow, Reaction::first()->type);
    }

    public function test_a_guest_has_only_one_reaction_per_recipe(): void
    {
        $recipe = Recipe::factory()->create();

        $this->withCookie('guest_token', self::GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like']);
        $this->withCookie('guest_token', self::GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'love']);

        $this->assertDatabaseCount('reactions', 1);

        $this->withCookie('guest_token', self::OTHER_GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'love']);

        $this->assertDatabaseCount('reactions', 2);
    }

    public function test_the_database_refuses_a_second_reaction_from_the_same_member(): void
    {
        $reaction = Reaction::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Reaction::factory()->create([
            'recipe_id' => $reaction->recipe_id,
            'user_id' => $reaction->user_id,
        ]);
    }

    public function test_reaction_type_must_be_valid(): void
    {
        $recipe = Recipe::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'angry'])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_cannot_react_to_a_recipe_that_is_not_public(): void
    {
        $draft = Recipe::factory()->draft()->create();

        $this->post("/recipes/{$draft->slug}/reactions", ['type' => 'like'])->assertNotFound();
        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_guest_reactions_can_be_switched_off(): void
    {
        config(['community.guest_reactions' => false]);
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like'])->assertForbidden();
    }

    public function test_guest_reactions_are_rate_limited(): void
    {
        $recipe = Recipe::factory()->create();

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like'])->assertRedirect();
        }

        $this->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like'])
            ->assertTooManyRequests();
    }

    public function test_recipe_page_shows_counts_and_the_viewers_own_reaction(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();
        Reaction::factory()->for($recipe)->count(2)->create(['type' => ReactionType::Like]);
        Reaction::factory()->for($recipe)->guest()->create(['type' => ReactionType::Love]);
        Reaction::factory()->for($recipe)->for($user)->create(['type' => ReactionType::Wow]);

        $this->actingAs($user)
            ->get("/recipes/{$recipe->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('reactions.counts', ['like' => 2, 'love' => 1, 'wow' => 1])
                ->where('reactions.mine', 'wow')
            );
    }

    public function test_guest_sees_their_own_reaction(): void
    {
        $recipe = Recipe::factory()->create();

        $this->withCookie('guest_token', self::GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'love']);

        $this->withCookie('guest_token', self::GUEST_TOKEN)
            ->get("/recipes/{$recipe->slug}")
            ->assertInertia(fn ($page) => $page->where('reactions.mine', 'love'));
    }

    public function test_viewing_a_recipe_does_not_hand_out_a_guest_cookie(): void
    {
        $recipe = Recipe::factory()->create();
        Reaction::factory()->for($recipe)->guest()->create();

        $this->get("/recipes/{$recipe->slug}")
            ->assertInertia(fn ($page) => $page->where('reactions.mine', null))
            ->assertCookieMissing('guest_token');
    }
}
