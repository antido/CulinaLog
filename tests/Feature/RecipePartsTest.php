<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class RecipePartsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function recipeData(array $overrides = []): array
    {
        return [
            'title' => 'Tomato Soup',
            'category_id' => Category::factory()->create()->id,
            'description' => 'Simple and red.',
            'prep_time' => 10,
            'cook_time' => 30,
            'servings' => 4,
            'difficulty' => 'easy',
            'status' => 'published',
            'ingredients' => [
                ['name' => 'Tomatoes', 'quantity' => '1.5', 'unit' => 'kg'],
                ['name' => 'Onion', 'quantity' => '1', 'unit' => ''],
                ['name' => 'Salt', 'quantity' => '', 'unit' => ''],
            ],
            'instructions' => [
                ['instruction' => 'Chop everything.'],
                ['instruction' => 'Simmer for 30 minutes.'],
                ['instruction' => 'Blend.'],
            ],
            ...$overrides,
        ];
    }

    public function test_recipe_is_saved_with_ingredients_and_steps_in_order(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/recipes', $this->recipeData())
            ->assertSessionHasNoErrors();

        $recipe = Recipe::first();

        $this->assertSame(
            ['Tomatoes', 'Onion', 'Salt'],
            $recipe->ingredients->pluck('name')->all(),
        );
        $this->assertSame([1, 2, 3], $recipe->ingredients->pluck('sort_order')->all());
        $this->assertNull($recipe->ingredients[2]->quantity);
        $this->assertNull($recipe->ingredients[1]->unit);

        $this->assertSame(
            ['Chop everything.', 'Simmer for 30 minutes.', 'Blend.'],
            $recipe->instructions->pluck('instruction')->all(),
        );
        $this->assertSame([1, 2, 3], $recipe->instructions->pluck('step_number')->all());
    }

    public function test_recipe_page_shows_parts_in_the_correct_order(): void
    {
        $recipe = Recipe::factory()->create();
        // Saved out of order on purpose: the page must sort them.
        $recipe->ingredients()->create(['name' => 'Second', 'quantity' => 2, 'sort_order' => 2]);
        $recipe->ingredients()->create(['name' => 'First', 'quantity' => 0.5, 'unit' => 'cup', 'sort_order' => 1]);
        $recipe->instructions()->create(['step_number' => 2, 'instruction' => 'Then this.']);
        $recipe->instructions()->create(['step_number' => 1, 'instruction' => 'Do this.']);

        $this->get("/recipes/{$recipe->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('recipe.ingredients.0.name', 'First')
                ->where('recipe.ingredients.0.quantity', '0.5')
                ->where('recipe.ingredients.0.unit', 'cup')
                ->where('recipe.ingredients.1.name', 'Second')
                ->where('recipe.ingredients.1.quantity', '2')
                ->where('recipe.instructions.0.instruction', 'Do this.')
                ->where('recipe.instructions.1.instruction', 'Then this.')
            );
    }

    public function test_updating_replaces_and_reorders_the_parts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/recipes', $this->recipeData());
        $recipe = Recipe::first();

        $this->actingAs($user)
            ->put("/recipes/{$recipe->slug}", $this->recipeData([
                'category_id' => $recipe->category_id,
                'ingredients' => [
                    ['name' => 'Salt', 'quantity' => '', 'unit' => ''],
                    ['name' => 'Tomatoes', 'quantity' => '2', 'unit' => 'kg'],
                ],
                'instructions' => [
                    ['instruction' => 'Only one step now.'],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $recipe->refresh();

        $this->assertSame(['Salt', 'Tomatoes'], $recipe->ingredients->pluck('name')->all());
        $this->assertSame([1, 2], $recipe->ingredients->pluck('sort_order')->all());
        $this->assertSame(['Only one step now.'], $recipe->instructions->pluck('instruction')->all());
        $this->assertDatabaseCount('ingredients', 2);
        $this->assertDatabaseCount('instructions', 1);
    }

    public function test_at_least_one_ingredient_and_one_step_are_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/recipes', $this->recipeData(['ingredients' => [], 'instructions' => []]))
            ->assertSessionHasErrors(['ingredients', 'instructions']);

        $this->assertDatabaseCount('recipes', 0);
    }

    public function test_ingredient_and_step_fields_are_validated(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/recipes', $this->recipeData([
                'ingredients' => [
                    ['name' => '', 'quantity' => '1', 'unit' => 'cup'],
                    ['name' => 'Flour', 'quantity' => 'a handful', 'unit' => 'cup'],
                    ['name' => 'Sugar', 'quantity' => '-2', 'unit' => str_repeat('x', 31)],
                    ['name' => str_repeat('n', 151), 'quantity' => '1', 'unit' => ''],
                ],
                'instructions' => [
                    ['instruction' => ''],
                    ['instruction' => str_repeat('s', 2001)],
                ],
            ]))
            ->assertSessionHasErrors([
                'ingredients.0.name',
                'ingredients.1.quantity',
                'ingredients.2.quantity',
                'ingredients.2.unit',
                'ingredients.3.name',
                'instructions.0.instruction',
                'instructions.1.instruction',
            ]);

        $this->assertDatabaseCount('recipes', 0);
        $this->assertDatabaseCount('ingredients', 0);
    }

    public function test_lists_are_limited_to_fifty_rows(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/recipes', $this->recipeData([
                'ingredients' => array_fill(0, 51, ['name' => 'Thing', 'quantity' => '1', 'unit' => '']),
            ]))
            ->assertSessionHasErrors('ingredients');
    }

    public function test_nothing_is_saved_when_a_part_fails_to_save(): void
    {
        // Make saving the second ingredient fail, as a database error would.
        Event::listen('eloquent.creating: '.Ingredient::class, function (Ingredient $ingredient): void {
            if ($ingredient->sort_order === 2) {
                throw new RuntimeException('Simulated failure');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->actingAs(User::factory()->create())->post('/recipes', $this->recipeData());
            $this->fail('The save should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated failure', $exception->getMessage());
        }

        // The transaction rolled back the recipe and the first ingredient too.
        $this->assertDatabaseCount('recipes', 0);
        $this->assertDatabaseCount('ingredients', 0);
        $this->assertDatabaseCount('instructions', 0);
    }

    public function test_other_users_cannot_change_a_recipes_parts(): void
    {
        $recipe = Recipe::factory()->create();
        $recipe->ingredients()->create(['name' => 'Original', 'sort_order' => 1]);

        $this->actingAs(User::factory()->create())
            ->put("/recipes/{$recipe->slug}", $this->recipeData([
                'ingredients' => [['name' => 'Poison', 'quantity' => '1', 'unit' => '']],
            ]))
            ->assertForbidden();

        $this->assertSame(['Original'], $recipe->fresh()->ingredients->pluck('name')->all());
    }

    public function test_parts_are_deleted_with_the_recipe(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/recipes', $this->recipeData());

        $this->actingAs($user)->delete('/recipes/'.Recipe::first()->slug);

        $this->assertDatabaseCount('ingredients', 0);
        $this->assertDatabaseCount('instructions', 0);
    }

    public function test_edit_form_receives_the_existing_parts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/recipes', $this->recipeData());

        $this->actingAs($user)
            ->get('/recipes/'.Recipe::first()->slug.'/edit')
            ->assertInertia(fn ($page) => $page
                ->component('Recipes/Edit')
                ->where('recipe.ingredients.0', ['name' => 'Tomatoes', 'quantity' => '1.5', 'unit' => 'kg'])
                ->where('recipe.ingredients.2', ['name' => 'Salt', 'quantity' => '', 'unit' => ''])
                ->where('recipe.instructions.2', ['instruction' => 'Blend.'])
            );
    }
}
