<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentUserSeeder extends Seeder
{
    /**
     * Create one account per role for local development.
     * Every account uses the password "password".
     *
     * DatabaseSeeder only calls this outside production.
     */
    public function run(): void
    {
        $accounts = [
            'admin' => ['name' => 'Ada Admin', 'username' => 'ada', 'email' => 'admin@culinalog.test'],
            'moderator' => ['name' => 'Moe Moderator', 'username' => 'moe', 'email' => 'moderator@culinalog.test'],
            'user' => ['name' => 'Uma User', 'username' => 'uma', 'email' => 'user@culinalog.test'],
        ];

        foreach ($accounts as $role => $account) {
            $user = User::firstWhere('email', $account['email'])
                ?? User::factory()->create($account);

            $user->syncRoles([$role]);
        }
    }
}
