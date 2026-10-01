<?php

namespace Database\Seeders;

use App\Enums\ForumStatus;
use App\Enums\RecipeDifficulty;
use App\Enums\RecipeStatus;
use App\Models\Category;
use App\Models\ForumCategory;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentContentSeeder extends Seeder
{
    /**
     * Create a few sample recipes for local development.
     * Run DevelopmentUserSeeder and CategorySeeder first.
     */
    public function run(): void
    {
        $authors = [
            'uma' => User::firstWhere('username', 'uma'),
            'moe' => User::firstWhere('username', 'moe'),
            'ada' => User::firstWhere('username', 'ada'),
        ];

        $this->seedForum($authors);

        foreach ($this->recipes() as $index => $data) {
            if (Recipe::where('title', $data['title'])->exists()) {
                continue;
            }

            $recipe = new Recipe([
                'category_id' => Category::firstWhere('name', $data['category'])->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'prep_time' => $data['prep_time'],
                'cook_time' => $data['cook_time'],
                'servings' => $data['servings'],
                'difficulty' => $data['difficulty'],
                'status' => $data['status'] ?? RecipeStatus::Published,
            ]);
            $recipe->user_id = $authors[$data['author']]->id;
            $recipe->slug = Recipe::uniqueSlug($data['title']);

            if ($recipe->status === RecipeStatus::Published) {
                // Spread the publish dates out so "newest first" has an order.
                $recipe->published_at = now()->subDays($index);
            }

            $recipe->save();

            foreach ($data['ingredients'] as $position => [$quantity, $unit, $name]) {
                $recipe->ingredients()->create([
                    'name' => $name,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'sort_order' => $position + 1,
                ]);
            }

            foreach ($data['instructions'] as $position => $instruction) {
                $recipe->instructions()->create([
                    'step_number' => $position + 1,
                    'instruction' => $instruction,
                ]);
            }
        }
    }

    /**
     * Create a few forum topics with replies.
     *
     * @param  array<string, User>  $authors
     */
    private function seedForum(array $authors): void
    {
        $topics = [
            [
                'author' => 'uma',
                'category' => 'Techniques',
                'title' => 'Why does my fried rice turn out mushy?',
                'body' => "I use freshly cooked rice and a non-stick pan. It always clumps and goes soft.\n\nWhat am I doing wrong?",
                'pinned' => false,
                'replies' => [
                    ['moe', 'Fresh rice has too much moisture. Cook it the day before and leave it uncovered in the fridge.'],
                    ['ada', 'Also do not crowd the pan. Two small batches fry better than one big one.'],
                ],
            ],
            [
                'author' => 'moe',
                'category' => 'Site feedback',
                'title' => 'Welcome to the forum: read this first',
                'body' => "Be kind, stay on topic, and say where a recipe came from if it is not yours.\n\nUse the Report button if something looks wrong and a moderator will take a look.",
                'pinned' => true,
                'replies' => [],
            ],
            [
                'author' => 'ada',
                'category' => 'Equipment',
                'title' => 'Is a cast iron pan worth the upkeep?',
                'body' => 'I keep hearing that it lasts forever, but also that it rusts if you look at it wrong. Who uses one daily?',
                'pinned' => false,
                'replies' => [
                    ['uma', 'Daily for six years. Dry it on the stove after washing and it is fine.'],
                ],
            ],
        ];

        foreach ($topics as $index => $data) {
            if (ForumTopic::where('title', $data['title'])->exists()) {
                continue;
            }

            $topic = new ForumTopic([
                'category_id' => ForumCategory::firstWhere('name', $data['category'])->id,
                'title' => $data['title'],
                'body' => $data['body'],
            ]);
            $topic->user_id = $authors[$data['author']]->id;
            $topic->slug = ForumTopic::uniqueSlug($data['title']);
            $topic->status = ForumStatus::Visible;
            $topic->is_pinned = $data['pinned'];
            $topic->last_activity_at = now()->subHours($index * 5);
            $topic->save();

            foreach ($data['replies'] as [$author, $body]) {
                $reply = new ForumReply(['body' => $body]);
                $reply->topic_id = $topic->id;
                $reply->user_id = $authors[$author]->id;
                $reply->status = ForumStatus::Visible;
                $reply->save();
            }
        }
    }

    /**
     * The sample recipes. Each ingredient is [quantity, unit, name].
     *
     * @return list<array<string, mixed>>
     */
    private function recipes(): array
    {
        return [
            [
                'author' => 'uma',
                'category' => 'Rice and noodles',
                'title' => 'Garlic Fried Rice',
                'description' => "The breakfast rice I grew up with. Day-old rice is not optional: fresh rice steams and turns soft, cold rice fries and stays in separate grains.\n\nGood next to a fried egg and anything salty.",
                'prep_time' => 5,
                'cook_time' => 10,
                'servings' => 2,
                'difficulty' => RecipeDifficulty::Easy,
                'ingredients' => [
                    [3, 'cups', 'cooked rice, chilled overnight'],
                    [6, 'cloves', 'garlic, finely chopped'],
                    [2, 'tbsp', 'neutral oil'],
                    [0.5, 'tsp', 'salt'],
                    [null, null, 'ground black pepper, to taste'],
                ],
                'instructions' => [
                    'Break up the cold rice with wet hands so there are no clumps.',
                    'Put the oil and garlic in a cold pan, then turn the heat to medium. Stir until the garlic is golden and smells nutty, about 3 minutes.',
                    'Scoop out a spoonful of the garlic to use as topping.',
                    'Turn the heat up, add the rice and salt, and toss for 4 to 5 minutes until every grain is hot and coated.',
                    'Season with pepper and finish with the reserved garlic.',
                ],
            ],
            [
                'author' => 'uma',
                'category' => 'Soups and stews',
                'title' => 'Chicken Tinola',
                'description' => 'A clear ginger and chicken soup with green papaya. It is the soup for rainy days and for anyone with a cold.',
                'prep_time' => 15,
                'cook_time' => 40,
                'servings' => 4,
                'difficulty' => RecipeDifficulty::Easy,
                'ingredients' => [
                    [1, 'kg', 'chicken, bone-in pieces'],
                    [1, null, 'thumb of ginger, sliced into strips'],
                    [1, null, 'onion, sliced'],
                    [4, 'cloves', 'garlic, crushed'],
                    [2, 'tbsp', 'fish sauce'],
                    [6, 'cups', 'water'],
                    [1, null, 'small green papaya, peeled and cut into wedges'],
                    [2, 'cups', 'chili leaves or spinach'],
                ],
                'instructions' => [
                    'Fry the ginger, onion and garlic in a little oil until soft and fragrant.',
                    'Add the chicken and fish sauce. Cook for 5 minutes, turning, until the chicken is no longer pink outside.',
                    'Pour in the water, bring to a boil, then lower to a simmer for 25 minutes. Skim off the foam.',
                    'Add the papaya and simmer for 8 more minutes, until a knife goes in easily.',
                    'Turn off the heat, stir in the leaves, and let them wilt for a minute before serving.',
                ],
            ],
            [
                'author' => 'moe',
                'category' => 'Baking',
                'title' => 'No-Knead Overnight Bread',
                'description' => 'Four ingredients and almost no work. Time does the kneading for you. You need a heavy pot with a lid.',
                'prep_time' => 15,
                'cook_time' => 45,
                'servings' => 8,
                'difficulty' => RecipeDifficulty::Medium,
                'ingredients' => [
                    [400, 'g', 'bread flour'],
                    [8, 'g', 'salt'],
                    [1, 'g', 'instant yeast'],
                    [300, 'ml', 'water, room temperature'],
                ],
                'instructions' => [
                    'Stir everything in a large bowl until no dry flour is left. The dough will be sticky and rough.',
                    'Cover and leave at room temperature for 12 to 18 hours, until doubled and bubbly.',
                    'Tip onto a floured surface, fold the edges into the middle to make a ball, and rest it seam-side down for 1 hour.',
                    'Heat the oven to 230 C with the lidded pot inside for at least 30 minutes.',
                    'Drop the dough into the hot pot, cover, and bake for 30 minutes. Remove the lid and bake 15 minutes more, until deep brown.',
                    'Cool on a rack for an hour before slicing.',
                ],
            ],
            [
                'author' => 'moe',
                'category' => 'Vegetarian',
                'title' => 'Eggplant Omelette',
                'description' => 'Whole eggplants charred over a flame, flattened, and fried in beaten egg. Smoky, soft and cheap.',
                'prep_time' => 10,
                'cook_time' => 15,
                'servings' => 2,
                'difficulty' => RecipeDifficulty::Easy,
                'ingredients' => [
                    [2, null, 'long eggplants'],
                    [2, null, 'eggs'],
                    [0.25, 'tsp', 'salt'],
                    [2, 'tbsp', 'oil'],
                ],
                'instructions' => [
                    'Char the eggplants directly over a gas flame or under a grill, turning, until the skin is black and the flesh is soft.',
                    'Peel off the skin, keeping the stem on. Flatten each eggplant with a fork.',
                    'Beat the eggs with the salt in a shallow dish and soak each eggplant in it.',
                    'Fry in hot oil for 3 minutes a side, pouring any leftover egg on top, until golden.',
                ],
            ],
            [
                'author' => 'ada',
                'category' => 'Desserts',
                'title' => 'Mango Float',
                'description' => 'An icebox cake of crackers, sweet cream and ripe mango. Make it the day before; it needs the night to set.',
                'prep_time' => 25,
                'cook_time' => 0,
                'servings' => 10,
                'difficulty' => RecipeDifficulty::Easy,
                'ingredients' => [
                    [200, 'g', 'graham crackers'],
                    [500, 'ml', 'all-purpose cream, chilled'],
                    [300, 'ml', 'sweetened condensed milk'],
                    [4, null, 'ripe mangoes, sliced thin'],
                ],
                'instructions' => [
                    'Whip the cold cream until it holds soft peaks, then fold in the condensed milk.',
                    'Line a dish with one layer of crackers. Spread a third of the cream over it and cover with mango.',
                    'Repeat twice more, finishing with mango on top.',
                    'Cover and chill for at least 8 hours, or freeze for 4 if you like it firm.',
                ],
            ],
            [
                'author' => 'ada',
                'category' => 'Drinks',
                'title' => 'Ginger Tea with Calamansi',
                'description' => 'Fresh ginger simmered until it bites, then softened with honey and citrus.',
                'prep_time' => 5,
                'cook_time' => 15,
                'servings' => 2,
                'difficulty' => RecipeDifficulty::Easy,
                'ingredients' => [
                    [1, null, 'large thumb of ginger, sliced'],
                    [3, 'cups', 'water'],
                    [2, 'tbsp', 'honey'],
                    [4, null, 'calamansi, or half a lemon'],
                ],
                'instructions' => [
                    'Simmer the ginger in the water for 15 minutes.',
                    'Strain into mugs, stir in the honey, and squeeze in the citrus.',
                ],
            ],
            [
                'author' => 'uma',
                'category' => 'Meat and poultry',
                'title' => 'Pork Adobo (work in progress)',
                'description' => 'Still adjusting the vinegar. Not ready to share.',
                'prep_time' => 10,
                'cook_time' => 50,
                'servings' => 4,
                'difficulty' => RecipeDifficulty::Medium,
                'status' => RecipeStatus::Draft,
                'ingredients' => [
                    [1, 'kg', 'pork belly, cubed'],
                    [0.5, 'cup', 'soy sauce'],
                    [0.5, 'cup', 'cane vinegar'],
                ],
                'instructions' => [
                    'Simmer everything together until the pork is tender.',
                ],
            ],
        ];
    }
}
