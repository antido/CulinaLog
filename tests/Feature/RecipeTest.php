<?php

namespace Tests\Feature;

use App\Enums\RecipeStatus;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A complete, valid recipe form.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function recipeData(array $overrides = []): array
    {
        return [
            'title' => 'Garlic Fried Rice',
            'category_id' => Category::factory()->create()->id,
            'description' => 'Day-old rice, lots of garlic.',
            'prep_time' => 5,
            'cook_time' => 10,
            'servings' => 2,
            'difficulty' => 'easy',
            'status' => 'published',
            'ingredients' => [
                ['name' => 'Cooked rice', 'quantity' => '2', 'unit' => 'cups'],
            ],
            'instructions' => [
                ['instruction' => 'Fry the garlic, then the rice.'],
            ],
            ...$overrides,
        ];
    }

    public function test_public_list_shows_only_visible_recipes(): void
    {
        Recipe::factory()->create(['title' => 'Visible Dish']);
        Recipe::factory()->draft()->create(['title' => 'Draft Dish']);
        Recipe::factory()->archived()->create(['title' => 'Archived Dish']);
        Recipe::factory()->hidden()->create(['title' => 'Hidden Dish']);

        $this->get('/recipes')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Recipes/Index')
                ->has('recipes.data', 1)
                ->where('recipes.data.0.title', 'Visible Dish')
            );
    }

    public function test_recipe_list_is_paginated(): void
    {
        Recipe::factory()->count(13)->create();

        $this->get('/recipes')->assertInertia(fn ($page) => $page
            ->has('recipes.data', 12)
            ->where('recipes.last_page', 2)
        );
    }

    public function test_anyone_can_view_a_published_recipe(): void
    {
        $recipe = Recipe::factory()->create(['title' => 'Open Dish']);

        $this->get("/recipes/{$recipe->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Recipes/Show')
                ->where('recipe.title', 'Open Dish')
                ->where('can.update', false)
                ->where('can.delete', false)
                ->where('can.moderate', false)
                ->missing('recipe.author.email')
            );
    }

    public function test_drafts_archived_and_hidden_recipes_are_not_public(): void
    {
        $other = User::factory()->create();

        $recipes = [
            Recipe::factory()->draft()->create(),
            Recipe::factory()->archived()->create(),
            Recipe::factory()->hidden()->create(),
        ];

        foreach ($recipes as $recipe) {
            $this->get("/recipes/{$recipe->slug}")->assertNotFound();
            $this->actingAs($other)->get("/recipes/{$recipe->slug}")->assertNotFound();
            $this->app['auth']->forgetGuards();
        }
    }

    public function test_author_can_view_their_own_draft(): void
    {
        $recipe = Recipe::factory()->draft()->create();

        $this->actingAs($recipe->user)
            ->get("/recipes/{$recipe->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('can.update', true));
    }

    public function test_moderator_can_view_a_hidden_recipe(): void
    {
        $recipe = Recipe::factory()->hidden()->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->get("/recipes/{$recipe->slug}")
            ->assertOk();
    }

    public function test_guests_cannot_create_recipes(): void
    {
        $this->get('/recipes/create')->assertRedirect('/login');
        $this->post('/recipes', $this->recipeData())->assertRedirect('/login');

        $this->assertDatabaseCount('recipes', 0);
    }

    public function test_unverified_users_cannot_create_recipes(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->post('/recipes', $this->recipeData())
            ->assertRedirect('/verify-email');

        $this->assertDatabaseCount('recipes', 0);
    }

    public function test_user_can_create_a_recipe_with_an_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/recipes', $this->recipeData([
                'image' => UploadedFile::fake()->image('rice.jpg', 3000, 2000),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/recipes/garlic-fried-rice');

        $recipe = Recipe::firstWhere('slug', 'garlic-fried-rice');

        $this->assertSame($user->id, $recipe->user_id);
        $this->assertSame(RecipeStatus::Published, $recipe->status);
        $this->assertNotNull($recipe->published_at);
        $this->assertFalse($recipe->is_hidden);
        Storage::disk('public')->assertExists($recipe->image);

        [$width] = getimagesizefromstring(Storage::disk('public')->get($recipe->image));
        $this->assertSame(1600, $width);
    }

    public function test_draft_has_no_published_date(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/recipes', $this->recipeData(['status' => 'draft']));

        $recipe = Recipe::first();

        $this->assertSame(RecipeStatus::Draft, $recipe->status);
        $this->assertNull($recipe->published_at);
    }

    public function test_owner_and_protected_fields_cannot_be_set_from_the_request(): void
    {
        $user = User::factory()->create();
        $victim = User::factory()->create();

        $this->actingAs($user)->post('/recipes', $this->recipeData([
            'user_id' => $victim->id,
            'slug' => 'chosen-slug',
            'is_hidden' => true,
            'published_at' => '2000-01-01 00:00:00',
            'image' => null,
        ]));

        $recipe = Recipe::first();

        $this->assertSame($user->id, $recipe->user_id);
        $this->assertSame('garlic-fried-rice', $recipe->slug);
        $this->assertFalse($recipe->is_hidden);
        $this->assertTrue($recipe->published_at->isToday());
    }

    public function test_recipe_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/recipes', [
                'title' => '',
                'category_id' => 999999,
                'description' => '',
                'prep_time' => -1,
                'cook_time' => 'soon',
                'servings' => 0,
                'difficulty' => 'impossible',
                'status' => 'deleted',
            ])
            ->assertSessionHasErrors([
                'title', 'category_id', 'description', 'prep_time',
                'cook_time', 'servings', 'difficulty', 'status',
            ]);

        $this->assertDatabaseCount('recipes', 0);
    }

    public function test_recipe_cannot_use_a_disabled_category(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->actingAs(User::factory()->create())
            ->post('/recipes', $this->recipeData(['category_id' => $category->id]))
            ->assertSessionHasErrors('category_id');
    }

    public function test_recipe_image_must_be_an_image(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/recipes', $this->recipeData([
                'image' => UploadedFile::fake()->createWithContent('dish.png', '<script>alert(1)</script>'),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('recipes', 0);
    }

    public function test_two_recipes_with_the_same_title_get_different_slugs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/recipes', $this->recipeData());
        $this->actingAs($user)->post('/recipes', $this->recipeData());

        $this->assertEqualsCanonicalizing(
            ['garlic-fried-rice', 'garlic-fried-rice-2'],
            Recipe::pluck('slug')->all(),
        );
    }

    public function test_a_recipe_titled_create_does_not_take_the_create_address(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/recipes', $this->recipeData(['title' => 'Create']));

        $this->assertSame('create-2', Recipe::first()->slug);
    }

    public function test_author_can_update_their_recipe(): void
    {
        $recipe = Recipe::factory()->draft()->create();

        $this->actingAs($recipe->user)
            ->put("/recipes/{$recipe->slug}", $this->recipeData([
                'title' => 'Better Title',
                'category_id' => $recipe->category_id,
                'status' => 'published',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect("/recipes/{$recipe->slug}");

        $recipe->refresh();

        $this->assertSame('Better Title', $recipe->title);
        $this->assertSame(RecipeStatus::Published, $recipe->status);
        $this->assertNotNull($recipe->published_at);
    }

    public function test_user_cannot_edit_or_delete_another_users_recipe(): void
    {
        $recipe = Recipe::factory()->create(['title' => 'Original']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get("/recipes/{$recipe->slug}/edit")->assertForbidden();

        $this->actingAs($intruder)
            ->put("/recipes/{$recipe->slug}", $this->recipeData(['title' => 'Hijacked']))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->patch("/recipes/{$recipe->slug}/status", ['status' => 'archived'])
            ->assertForbidden();

        $this->actingAs($intruder)->delete("/recipes/{$recipe->slug}")->assertForbidden();

        $recipe->refresh();

        $this->assertSame('Original', $recipe->title);
        $this->assertSame(RecipeStatus::Published, $recipe->status);
    }

    public function test_sending_your_own_user_id_does_not_take_over_a_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->put("/recipes/{$recipe->slug}", $this->recipeData(['user_id' => $intruder->id]))
            ->assertForbidden();

        $this->assertNotSame($intruder->id, $recipe->fresh()->user_id);
    }

    public function test_author_cannot_unhide_their_own_moderated_recipe(): void
    {
        $recipe = Recipe::factory()->hidden()->create();

        $this->actingAs($recipe->user)
            ->put("/recipes/{$recipe->slug}", $this->recipeData([
                'category_id' => $recipe->category_id,
                'is_hidden' => false,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($recipe->fresh()->is_hidden);

        $this->actingAs($recipe->user)
            ->patch("/moderation/recipes/{$recipe->slug}/restore")
            ->assertForbidden();

        $this->assertTrue($recipe->fresh()->is_hidden);
    }

    public function test_author_can_publish_and_archive_from_the_dashboard(): void
    {
        $recipe = Recipe::factory()->draft()->create();

        $this->actingAs($recipe->user)
            ->patch("/recipes/{$recipe->slug}/status", ['status' => 'published']);

        $this->assertSame(RecipeStatus::Published, $recipe->fresh()->status);
        $this->assertNotNull($recipe->fresh()->published_at);

        $this->actingAs($recipe->user)
            ->patch("/recipes/{$recipe->slug}/status", ['status' => 'archived']);

        $this->assertSame(RecipeStatus::Archived, $recipe->fresh()->status);

        $this->actingAs($recipe->user)
            ->patch("/recipes/{$recipe->slug}/status", ['status' => 'nonsense'])
            ->assertSessionHasErrors('status');
    }

    public function test_author_can_delete_their_recipe_and_its_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/recipes', $this->recipeData([
            'image' => UploadedFile::fake()->image('rice.jpg'),
        ]));
        $recipe = Recipe::first();

        $this->actingAs($user)
            ->delete("/recipes/{$recipe->slug}")
            ->assertRedirect('/dashboard');

        $this->assertNull($recipe->fresh());
        Storage::disk('public')->assertMissing($recipe->image);
    }

    public function test_moderator_can_hide_and_restore_but_not_edit_or_delete(): void
    {
        $recipe = Recipe::factory()->create(['title' => 'Original']);
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)->patch("/moderation/recipes/{$recipe->slug}/hide");
        $this->assertTrue($recipe->fresh()->is_hidden);

        $this->actingAs($moderator)->patch("/moderation/recipes/{$recipe->slug}/restore");
        $this->assertFalse($recipe->fresh()->is_hidden);

        $this->actingAs($moderator)
            ->put("/recipes/{$recipe->slug}", $this->recipeData(['title' => 'Changed']))
            ->assertForbidden();
        $this->actingAs($moderator)->delete("/recipes/{$recipe->slug}")->assertForbidden();

        $this->assertSame('Original', $recipe->fresh()->title);
    }

    public function test_users_cannot_hide_recipes(): void
    {
        $recipe = Recipe::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch("/moderation/recipes/{$recipe->slug}/hide")
            ->assertForbidden();

        $this->get('/login');
        $this->app['auth']->forgetGuards();
        $this->patch("/moderation/recipes/{$recipe->slug}/hide")->assertRedirect('/login');

        $this->assertFalse($recipe->fresh()->is_hidden);
    }

    public function test_admin_can_edit_and_delete_any_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put("/recipes/{$recipe->slug}", $this->recipeData([
                'title' => 'Fixed By Admin',
                'category_id' => $recipe->category_id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Fixed By Admin', $recipe->fresh()->title);
        $this->assertNotSame($admin->id, $recipe->fresh()->user_id);

        $this->actingAs($admin)->delete("/recipes/{$recipe->slug}");
        $this->assertNull($recipe->fresh());
    }

    public function test_dashboard_lists_only_the_users_own_recipes(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->for($user)->draft()->create(['title' => 'My Draft']);
        Recipe::factory()->create(['title' => 'Someone Else']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->has('recipes.data', 1)
                ->where('recipes.data.0.title', 'My Draft')
            );
    }

    public function test_category_page_lists_its_visible_recipes(): void
    {
        $category = Category::factory()->create();
        Recipe::factory()->for($category)->create(['title' => 'In Category']);
        Recipe::factory()->for($category)->draft()->create();
        Recipe::factory()->create();

        $this->get("/categories/{$category->slug}")
            ->assertInertia(fn ($page) => $page
                ->has('recipes.data', 1)
                ->where('recipes.data.0.title', 'In Category')
            );
    }

    public function test_category_with_recipes_cannot_be_deleted(): void
    {
        $recipe = Recipe::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete("/admin/categories/{$recipe->category->slug}")
            ->assertSessionHas('error');

        $this->assertNotNull($recipe->category->fresh());
    }

    public function test_profile_counts_only_visible_recipes(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->for($user)->count(2)->create();
        Recipe::factory()->for($user)->draft()->create();
        Recipe::factory()->for($user)->hidden()->create();

        $this->get("/users/{$user->username}")
            ->assertInertia(fn ($page) => $page
                ->where('stats.0.label', 'Recipes')
                ->where('stats.0.value', 2)
                ->has('recipes.data', 2)
            );
    }

    public function test_deleting_a_user_deletes_their_recipes(): void
    {
        $recipe = Recipe::factory()->create();

        $recipe->user->delete();

        $this->assertNull($recipe->fresh());
    }
}
