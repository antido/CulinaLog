<?php

namespace Database\Seeders;

use App\Models\ForumCategory;
use Illuminate\Database\Seeder;

class ForumCategorySeeder extends Seeder
{
    /**
     * Create the starting set of forum categories. Safe to run more than once.
     */
    public function run(): void
    {
        $categories = [
            'General cooking' => 'Anything about cooking that does not fit elsewhere.',
            'Techniques' => 'How to chop, sear, proof, temper and everything else.',
            'Ingredients and substitutions' => 'What to buy, what to swap, and what went wrong.',
            'Equipment' => 'Pans, knives, ovens and gadgets worth (or not worth) having.',
            'Site feedback' => 'Ideas and problems with CulinaLog itself.',
        ];

        $position = 1;

        foreach ($categories as $name => $description) {
            if (ForumCategory::where('name', $name)->doesntExist()) {
                $category = new ForumCategory([
                    'name' => $name,
                    'description' => $description,
                    'sort_order' => $position,
                ]);
                $category->slug = ForumCategory::uniqueSlug($name);
                $category->save();
            }

            $position++;
        }
    }
}
