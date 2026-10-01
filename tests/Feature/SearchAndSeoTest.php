<?php

namespace Tests\Feature;

use App\Enums\RecipeDifficulty;
use App\Models\Category;
use App\Models\ForumTopic;
use App\Models\Reaction;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchAndSeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The titles of the recipes a search returns, in order.
     *
     * @return list<string>
     */
    private function recipeTitles(string $query): array
    {
        $response = $this->get('/search?'.$query)->assertOk();

        return collect($response->viewData('page')['props']['results']['data'])->pluck('title')->all();
    }

    public function test_search_matches_title_description_ingredient_category_and_author(): void
    {
        $byTitle = Recipe::factory()->create(['title' => 'Saffron Rice']);
        $byDescription = Recipe::factory()->create(['title' => 'Plain One', 'description' => 'Coloured with saffron.']);
        $byIngredient = Recipe::factory()->create(['title' => 'Plain Two']);
        $byIngredient->ingredients()->create(['name' => 'Saffron threads', 'sort_order' => 1]);
        $byCategory = Recipe::factory()->for(Category::factory()->create(['name' => 'Saffron Dishes']))->create(['title' => 'Plain Three']);
        $byAuthor = Recipe::factory()->for(User::factory()->create(['name' => 'Sam Saffron']))->create(['title' => 'Plain Four']);
        Recipe::factory()->create(['title' => 'Unrelated', 'description' => 'Nothing to see.']);

        $this->assertEqualsCanonicalizing(
            ['Saffron Rice', 'Plain One', 'Plain Two', 'Plain Three', 'Plain Four'],
            $this->recipeTitles('q=saffron'),
        );
    }

    public function test_search_only_returns_public_recipes(): void
    {
        Recipe::factory()->create(['title' => 'Public Curry']);
        Recipe::factory()->draft()->create(['title' => 'Draft Curry']);
        Recipe::factory()->archived()->create(['title' => 'Archived Curry']);
        Recipe::factory()->hidden()->create(['title' => 'Hidden Curry']);

        $this->assertSame(['Public Curry'], $this->recipeTitles('q=curry'));
    }

    public function test_recipes_can_be_filtered_by_category_difficulty_and_time(): void
    {
        $soups = Category::factory()->create(['name' => 'Soups']);
        Recipe::factory()->for($soups)->create([
            'title' => 'Quick Easy Soup', 'difficulty' => RecipeDifficulty::Easy, 'prep_time' => 5, 'cook_time' => 10,
        ]);
        Recipe::factory()->for($soups)->create([
            'title' => 'Slow Hard Soup', 'difficulty' => RecipeDifficulty::Hard, 'prep_time' => 30, 'cook_time' => 120,
        ]);
        Recipe::factory()->create([
            'title' => 'Quick Easy Salad', 'difficulty' => RecipeDifficulty::Easy, 'prep_time' => 10, 'cook_time' => 0,
        ]);

        $this->assertEqualsCanonicalizing(['Quick Easy Soup', 'Slow Hard Soup'], $this->recipeTitles('category=soups'));
        $this->assertEqualsCanonicalizing(['Quick Easy Soup', 'Quick Easy Salad'], $this->recipeTitles('difficulty=easy'));
        $this->assertEqualsCanonicalizing(['Quick Easy Soup', 'Quick Easy Salad'], $this->recipeTitles('max_time=15'));
        $this->assertSame(['Quick Easy Soup'], $this->recipeTitles('category=soups&difficulty=easy&max_time=30'));
    }

    public function test_results_can_be_sorted_by_newest_oldest_and_popularity(): void
    {
        $old = Recipe::factory()->create(['title' => 'Old', 'published_at' => now()->subDays(10)]);
        $new = Recipe::factory()->create(['title' => 'New', 'published_at' => now()]);
        $popular = Recipe::factory()->create(['title' => 'Popular', 'published_at' => now()->subDays(5)]);
        Reaction::factory()->for($popular)->count(3)->create();
        Reaction::factory()->for($old)->create();

        $this->assertSame(['New', 'Popular', 'Old'], $this->recipeTitles('sort=newest'));
        $this->assertSame(['Old', 'Popular', 'New'], $this->recipeTitles('sort=oldest'));
        $this->assertSame(['Popular', 'Old', 'New'], $this->recipeTitles('sort=popular'));
    }

    public function test_search_results_are_paginated_and_keep_the_filters(): void
    {
        Recipe::factory()->count(13)->create(['description' => 'A noodle dish.']);

        $this->get('/search?q=noodle&sort=oldest')
            ->assertInertia(fn ($page) => $page
                ->component('Search/Index')
                ->has('results.data', 12)
                ->where('results.total', 13)
                ->where('results.next_page_url', fn ($url) => str_contains($url, 'q=noodle') && str_contains($url, 'sort=oldest'))
            );
    }

    public function test_search_can_look_in_forum_topics(): void
    {
        ForumTopic::factory()->create(['title' => 'Best wok for a small stove']);
        ForumTopic::factory()->create(['title' => 'Other', 'body' => 'Is a carbon steel wok worth it?']);
        ForumTopic::factory()->hidden()->create(['title' => 'Hidden wok topic']);
        Recipe::factory()->create(['title' => 'Wok Fried Greens']);

        $this->get('/search?type=topics&q=wok')
            ->assertInertia(fn ($page) => $page
                ->where('filters.type', 'topics')
                ->has('results.data', 2)
                ->has('results.data.0.replies_count')
            );
    }

    public function test_unknown_filter_values_are_ignored(): void
    {
        Recipe::factory()->count(2)->create();

        $this->get('/search?category=nope&difficulty=extreme&max_time=7&sort=random&type=users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.category', '')
                ->where('filters.difficulty', '')
                ->where('filters.max_time', '')
                ->where('filters.sort', 'newest')
                ->where('filters.type', 'recipes')
                ->has('results.data', 2)
            );
    }

    public function test_sql_and_wildcard_characters_are_searched_as_plain_text(): void
    {
        Recipe::factory()->count(3)->create();
        Recipe::factory()->create(['title' => '100% Rye Bread']);

        $this->assertSame([], $this->recipeTitles('q='.urlencode("' OR '1'='1")));
        $this->assertSame([], $this->recipeTitles('q='.urlencode('"; DROP TABLE recipes; --')));
        $this->assertSame([], $this->recipeTitles('q='.urlencode('_____')));
        $this->assertSame(['100% Rye Bread'], $this->recipeTitles('q='.urlencode('100%')));

        $this->assertDatabaseCount('recipes', 4);
    }

    public function test_search_does_not_run_a_query_per_result(): void
    {
        Recipe::factory()->count(12)->create(['description' => 'Tasty stew.']);

        DB::enableQueryLog();
        $this->get('/search?q=stew&sort=popular')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // A count, the page of recipes, their authors, their categories and
        // the category list: a handful of queries however many results there are.
        $this->assertLessThanOrEqual(8, $queryCount);
    }

    public function test_recipe_list_does_not_run_a_query_per_recipe(): void
    {
        Recipe::factory()->count(12)->create();

        DB::enableQueryLog();
        $this->get('/recipes')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(6, $queryCount);
    }

    public function test_recipe_page_has_seo_tags_and_structured_data(): void
    {
        $recipe = Recipe::factory()
            ->for(User::factory()->create(['name' => 'Rosa Cruz']))
            ->for(Category::factory()->create(['name' => 'Soups']))
            ->create([
                'title' => 'Lentil Soup',
                'description' => "Warm and cheap.\nGood with bread.",
                'prep_time' => 15,
                'cook_time' => 60,
                'servings' => 4,
            ]);
        $recipe->ingredients()->create(['name' => 'red lentils', 'quantity' => 1.5, 'unit' => 'cups', 'sort_order' => 1]);
        $recipe->instructions()->create(['step_number' => 1, 'instruction' => 'Simmer until soft.']);

        $response = $this->get("/recipes/{$recipe->slug}")->assertOk();

        $response->assertSee('<title inertia>Lentil Soup by Rosa Cruz - '.config('app.name').'</title>', false);
        $response->assertSee('<meta name="description" content="Warm and cheap. Good with bread.">', false);
        $response->assertSee('<link rel="canonical" href="'.url("/recipes/{$recipe->slug}").'">', false);
        $response->assertSee('<meta property="og:title" content="Lentil Soup by Rosa Cruz">', false);
        $response->assertSee('<meta property="og:type" content="article">', false);
        $response->assertDontSee('name="robots"', false);

        $jsonLd = $response->viewData('meta')['jsonLd'];

        $this->assertSame('Recipe', $jsonLd['@type']);
        $this->assertSame('Lentil Soup', $jsonLd['name']);
        $this->assertSame('Rosa Cruz', $jsonLd['author']['name']);
        $this->assertSame('PT15M', $jsonLd['prepTime']);
        $this->assertSame('PT1H', $jsonLd['cookTime']);
        $this->assertSame('PT1H15M', $jsonLd['totalTime']);
        $this->assertSame('4 servings', $jsonLd['recipeYield']);
        $this->assertSame('Soups', $jsonLd['recipeCategory']);
        $this->assertSame(['1.5 cups red lentils'], $jsonLd['recipeIngredient']);
        $this->assertSame('Simmer until soft.', $jsonLd['recipeInstructions'][0]['text']);

        $response->assertSee('<script type="application/ld+json"', false);
    }

    public function test_structured_data_cannot_break_out_of_its_script_tag(): void
    {
        $recipe = Recipe::factory()->create([
            'title' => '</script><script>alert("seo")</script>',
            'description' => 'Harmless description.',
        ]);

        $response = $this->get("/recipes/{$recipe->slug}")->assertOk();

        $response->assertDontSee('</script><script>alert("seo")</script>', false);
        $response->assertDontSee('<script>alert(', false);
    }

    public function test_each_recipe_page_has_its_own_title_and_canonical_address(): void
    {
        $first = Recipe::factory()->create(['title' => 'First Dish']);
        $second = Recipe::factory()->create(['title' => 'Second Dish']);

        $this->get("/recipes/{$first->slug}?comments_page=2")
            ->assertSee('<link rel="canonical" href="'.url("/recipes/{$first->slug}").'">', false)
            ->assertSee('First Dish by', false);

        $this->get("/recipes/{$second->slug}")
            ->assertSee('<link rel="canonical" href="'.url("/recipes/{$second->slug}").'">', false)
            ->assertDontSee('First Dish', false);
    }

    public function test_private_recipes_and_search_pages_are_marked_noindex(): void
    {
        $draft = Recipe::factory()->draft()->create();

        $this->actingAs($draft->user)
            ->get("/recipes/{$draft->slug}")
            ->assertSee('<meta name="robots" content="noindex">', false);

        $this->get('/search?q=anything')->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_pages_without_their_own_details_fall_back_to_site_defaults(): void
    {
        $this->get('/categories')
            ->assertSee('<title inertia>'.config('app.name').'</title>', false)
            ->assertSee('<meta name="description" content="CulinaLog is a community recipe book', false);
    }
}
