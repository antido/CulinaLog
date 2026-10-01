<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed the database after it is migrated, so every test that uses
     * RefreshDatabase starts with the roles and permissions in place.
     *
     * @var bool
     */
    protected $seed = true;

    /**
     * @var class-string
     */
    protected $seeder = RolesAndPermissionsSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests check what the server sends, not the compiled JavaScript,
        // so they must pass without running `npm run build` first.
        $this->withoutVite();
    }
}
