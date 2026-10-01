<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_three_roles_are_seeded_with_their_permissions(): void
    {
        $this->assertEqualsCanonicalizing(
            ['admin', 'moderator', 'user'],
            Role::pluck('name')->all(),
        );

        $this->assertTrue(Role::findByName('user')->hasPermissionTo('recipes.create'));
        $this->assertFalse(Role::findByName('user')->hasPermissionTo('reports.review'));
        $this->assertTrue(Role::findByName('moderator')->hasPermissionTo('reports.review'));
        $this->assertFalse(Role::findByName('moderator')->hasPermissionTo('users.manage'));

        foreach (array_keys(RolesAndPermissionsSeeder::PERMISSIONS) as $permission) {
            $this->assertTrue(Role::findByName('admin')->hasPermissionTo($permission));
        }
    }

    public function test_new_registrations_receive_the_user_role(): void
    {
        $this->post('/register', [
            'name' => 'New Cook',
            'username' => 'new_cook',
            'email' => 'cook@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::firstWhere('email', 'cook@example.com');

        $this->assertSame(['user'], $user->getRoleNames()->all());
    }

    public function test_registration_ignores_a_role_sent_by_the_browser(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky Cook',
            'username' => 'sneaky_cook',
            'email' => 'sneaky@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
            'roles' => ['admin'],
            'is_admin' => true,
        ]);

        $user = User::firstWhere('email', 'sneaky@example.com');

        $this->assertSame(['user'], $user->getRoleNames()->all());
        $this->assertFalse($user->can('dashboard.admin'));
    }

    public function test_profile_update_cannot_change_the_users_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => 'admin',
            'roles' => ['admin'],
        ]);

        $this->assertSame(['user'], $user->fresh()->getRoleNames()->all());
    }

    public function test_guests_are_redirected_to_login_from_protected_pages(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
        $this->get('/moderation')->assertRedirect('/login');
    }

    public function test_signed_in_users_can_open_their_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_unverified_users_cannot_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/dashboard')
            ->assertRedirect('/verify-email');
    }

    public function test_users_cannot_open_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
    }

    public function test_users_cannot_open_moderation_routes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/moderation')
            ->assertForbidden();
    }

    public function test_moderators_can_open_moderation_but_not_admin_routes(): void
    {
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)->get('/moderation')->assertOk();
        $this->actingAs($moderator)->get('/admin')->assertForbidden();
        $this->actingAs($moderator)->get('/admin/users')->assertForbidden();
    }

    public function test_admins_can_open_admin_and_moderation_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/moderation')->assertOk();
    }

    public function test_access_follows_the_permission_not_the_role_name(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('dashboard.moderator');

        $this->actingAs($user)->get('/moderation')->assertOk();
    }

    public function test_admin_can_change_another_users_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/role", ['role' => 'moderator'])
            ->assertSessionHasNoErrors();

        $this->assertSame(['moderator'], $user->fresh()->getRoleNames()->all());
    }

    public function test_user_cannot_assign_themselves_the_admin_role(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch("/admin/users/{$user->id}/role", ['role' => 'admin'])
            ->assertForbidden();

        $this->assertSame(['user'], $user->fresh()->getRoleNames()->all());
    }

    public function test_moderator_cannot_promote_anyone(): void
    {
        $moderator = User::factory()->moderator()->create();
        $user = User::factory()->create();

        $this->actingAs($moderator)
            ->patch("/admin/users/{$user->id}/role", ['role' => 'admin'])
            ->assertForbidden();

        $this->actingAs($moderator)
            ->patch("/admin/users/{$moderator->id}/role", ['role' => 'admin'])
            ->assertForbidden();

        $this->assertSame(['user'], $user->fresh()->getRoleNames()->all());
        $this->assertSame(['moderator'], $moderator->fresh()->getRoleNames()->all());
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch("/admin/users/{$admin->id}/role", ['role' => 'user'])
            ->assertForbidden();

        $this->assertSame(['admin'], $admin->fresh()->getRoleNames()->all());
    }

    public function test_role_must_be_an_existing_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch("/admin/users/{$user->id}/role", ['role' => 'superuser'])
            ->assertSessionHasErrors('role');
    }

    public function test_only_admins_can_delete_users(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $user = User::factory()->create();

        $this->actingAs($moderator)->delete("/admin/users/{$user->id}")->assertForbidden();
        $this->assertNotNull($user->fresh());

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertForbidden();
        $this->assertNotNull($admin->fresh());

        $this->actingAs($admin)->delete("/admin/users/{$user->id}")->assertRedirect();
        $this->assertNull($user->fresh());
    }

    public function test_only_safe_user_fields_are_shared_with_the_browser(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $user->id)
                ->missing('auth.user.password')
                ->missing('auth.user.remember_token')
                ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('recipes.create')
                    && ! collect($permissions)->contains('dashboard.admin'))
            );
    }
}
