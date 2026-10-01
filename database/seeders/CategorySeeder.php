<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Create the starting set of recipe categories. Safe to run more than once.
     */
    public function run(): void
    {
        $categories = [
            'Breakfast' => 'Eggs, porridge, pancakes and other ways to start the day.',
            'Soups and stews' => 'One-pot dishes that simmer.',
            'Rice and noodles' => 'Fried rice, pasta, ramen and everything in between.',
            'Meat and poultry' => 'Roasts, grills, braises and stir-fries.',
            'Fish and seafood' => 'From quick pan-fried fillets to slow curries.',
            'Vegetarian' => 'Meals without meat or fish.',
            'Baking' => 'Bread, pastries, cakes and cookies.',
            'Desserts' => 'Something sweet to finish.',
            'Drinks' => 'Hot, cold, with or without alcohol.',
        ];

        foreach ($categories as $name => $description) {
            if (Category::where('name', $name)->doesntExist()) {
                $category = new Category(['name' => $name, 'description' => $description]);
                $category->slug = Category::uniqueSlug($name);
                $category->save();
            }
        }
    }
}
