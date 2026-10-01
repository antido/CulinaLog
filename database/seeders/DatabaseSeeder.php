<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(ForumCategorySeeder::class);

        // Sample accounts and content use a well-known password,
        // so they must never be created on a live site.
        if (! app()->isProduction()) {
            $this->call(DevelopmentUserSeeder::class);
            $this->call(DevelopmentContentSeeder::class);
        }
    }
}
