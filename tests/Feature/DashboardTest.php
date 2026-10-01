<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\Recipe;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_site_totals_and_recent_activity(): void
    {
        $admin = User::factory()->admin()->create();
        Recipe::factory()->count(2)->create();
        Comment::factory()->create();
        Comment::factory()->pending()->create();
        ForumTopic::factory()->create();
        Report::factory()->create();
        Report::factory()->resolved()->create();
        // Created last, so it is the newest recipe.
        Recipe::factory()->draft()->create(['title' => 'Newest Draft']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(function ($page): void {
                $stats = collect($page->toArray()['props']['stats'])->pluck('value', 'label');

                $this->assertSame(User::count(), $stats['Total users']);
                $this->assertSame(Recipe::count(), $stats['Total recipes']);
                $this->assertSame(Recipe::where('status', 'published')->count(), $stats['Published recipes']);
                $this->assertSame(1, $stats['Draft recipes']);
                $this->assertSame(2, $stats['Comments']);
                $this->assertSame(1, $stats['Pending comments']);
                $this->assertSame(1, $stats['Forum topics']);
                $this->assertSame(1, $stats['Pending reports']);

                $page->component('Admin/Dashboard')
                    ->has('recentRecipes', 5)
                    ->where('recentRecipes.0.title', 'Newest Draft')
                    ->has('recentComments', 2)
                    ->has('recentUsers', 5)
                    ->has('recentTopics', 1)
                    ->missing('recentUsers.0.email');
            });
    }

    public function test_moderator_dashboard_shows_the_moderation_workload(): void
    {
        $moderator = User::factory()->moderator()->create();
        Comment::factory()->pending()->count(2)->create();
        ForumReply::factory()->guest()->pending()->create();
        Report::factory()->create();
        $recipe = Recipe::factory()->create(['title' => 'Flagged Dish']);

        $this->actingAs($moderator)->patch("/moderation/recipes/{$recipe->slug}/hide");

        $this->actingAs($moderator)
            ->get('/moderation')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Moderation/Dashboard')
                ->where('stats.0.value', 2)
                ->where('stats.1.value', 1)
                ->where('stats.2.value', 1)
                ->has('recentReports', 1)
                ->has('activity', 1)
                ->where('activity.0.title', "{$moderator->name} hid Recipe \"Flagged Dish\"")
            );
    }

    public function test_dashboards_are_separated_by_role(): void
    {
        $user = User::factory()->create();
        $moderator = User::factory()->moderator()->create();
        $admin = User::factory()->admin()->create();

        $adminPages = ['/admin', '/admin/users', '/admin/roles', '/admin/permissions', '/admin/recipes', '/admin/categories', '/admin/forum-categories'];
        $moderatorPages = ['/moderation', '/moderation/reports', '/moderation/pending'];

        foreach ($adminPages as $page) {
            $this->get($page)->assertRedirect('/login');
            $this->actingAs($user)->get($page)->assertForbidden();
            $this->actingAs($moderator)->get($page)->assertForbidden();
            $this->actingAs($admin)->get($page)->assertOk();
            $this->app['auth']->forgetGuards();
        }

        foreach ($moderatorPages as $page) {
            $this->get($page)->assertRedirect('/login');
            $this->actingAs($user)->get($page)->assertForbidden();
            $this->actingAs($moderator)->get($page)->assertOk();
            $this->actingAs($admin)->get($page)->assertOk();
            $this->app['auth']->forgetGuards();
        }
    }

    public function test_unverified_admin_cannot_open_the_dashboards(): void
    {
        $admin = User::factory()->admin()->unverified()->create();

        $this->actingAs($admin)->get('/admin')->assertRedirect('/verify-email');
        $this->actingAs($admin)->get('/moderation')->assertRedirect('/verify-email');
    }

    public function test_report_details_need_the_reports_permission(): void
    {
        $helper = User::factory()->create();
        $helper->givePermissionTo('dashboard.moderator');
        Report::factory()->create();

        $this->actingAs($helper)
            ->get('/moderation')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('recentReports', 0)->where('stats.2.href', null));
    }

    public function test_admin_recipe_list_includes_every_status_and_can_be_filtered(): void
    {
        $admin = User::factory()->admin()->create();
        Recipe::factory()->create(['title' => 'Public Pie']);
        Recipe::factory()->draft()->create(['title' => 'Draft Pie']);
        Recipe::factory()->hidden()->create(['title' => 'Hidden Cake']);

        $this->actingAs($admin)->get('/admin/recipes')
            ->assertInertia(fn ($page) => $page->has('recipes.data', 3));

        $this->actingAs($admin)->get('/admin/recipes?search=pie&status=draft')
            ->assertInertia(fn ($page) => $page
                ->has('recipes.data', 1)
                ->where('recipes.data.0.title', 'Draft Pie')
            );
    }

    public function test_admin_can_create_a_role_and_set_its_permissions(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/roles', ['name' => 'recipe_editor', 'permissions' => ['recipes.manage']])
            ->assertSessionHasNoErrors();

        $role = Role::findByName('recipe_editor');
        $this->assertTrue($role->hasPermissionTo('recipes.manage'));

        $this->actingAs($admin)
            ->put("/admin/roles/{$role->id}", ['permissions' => ['recipes.manage', 'categories.manage']])
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            ['recipes.manage', 'categories.manage'],
            $role->fresh()->permissions->pluck('name')->all(),
        );
    }

    public function test_changing_a_roles_permissions_takes_effect(): void
    {
        $admin = User::factory()->admin()->create();
        $moderator = User::factory()->moderator()->create();
        $role = Role::findByName('moderator');

        $this->actingAs($moderator)->get('/moderation/reports')->assertOk();

        $this->actingAs($admin)->put("/admin/roles/{$role->id}", [
            'permissions' => ['dashboard.moderator', 'comments.moderate'],
        ]);

        $this->actingAs($moderator->fresh())->get('/moderation/reports')->assertForbidden();
        $this->actingAs($moderator->fresh())->get('/moderation')->assertOk();
    }

    public function test_role_input_is_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/roles', ['name' => 'Bad Name!', 'permissions' => []])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post('/admin/roles', ['name' => 'admin', 'permissions' => []])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post('/admin/roles', ['name' => 'helper', 'permissions' => ['everything.allowed']])
            ->assertSessionHasErrors('permissions.0');

        $role = Role::findByName('user');
        $this->actingAs($admin)
            ->put("/admin/roles/{$role->id}", ['name' => 'renamed', 'permissions' => []])
            ->assertSessionHasErrors('name');
    }

    public function test_the_admin_role_cannot_be_changed_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::findByName('admin');

        $this->actingAs($admin)
            ->put("/admin/roles/{$role->id}", ['permissions' => []])
            ->assertSessionHas('error');
        $this->actingAs($admin)->delete("/admin/roles/{$role->id}")->assertSessionHas('error');

        $this->assertTrue($role->fresh()->hasPermissionTo('roles.manage'));
    }

    public function test_only_unused_custom_roles_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $custom = Role::create(['name' => 'taster']);
        $member = User::factory()->create();
        $member->syncRoles(['taster']);

        $this->actingAs($admin)->delete("/admin/roles/{$custom->id}")->assertSessionHas('error');
        $this->assertNotNull(Role::find($custom->id));

        $member->syncRoles(['user']);
        $this->actingAs($admin)->delete("/admin/roles/{$custom->id}")->assertSessionHas('success');
        $this->assertNull(Role::find($custom->id));

        $this->actingAs($admin)
            ->delete('/admin/roles/'.Role::findByName('moderator')->id)
            ->assertSessionHas('error');
    }

    public function test_moderators_and_users_cannot_manage_roles(): void
    {
        $role = Role::findByName('user');

        foreach ([User::factory()->create(), User::factory()->moderator()->create()] as $person) {
            $this->actingAs($person)
                ->post('/admin/roles', ['name' => 'sneaky', 'permissions' => ['roles.manage']])
                ->assertForbidden();
            $this->actingAs($person)
                ->put("/admin/roles/{$role->id}", ['permissions' => ['dashboard.admin', 'roles.manage']])
                ->assertForbidden();
            $this->actingAs($person)->delete("/admin/roles/{$role->id}")->assertForbidden();
        }

        $this->assertFalse($role->fresh()->hasPermissionTo('dashboard.admin'));
        $this->assertSame(3, Role::count());
    }

    public function test_permissions_page_lists_permissions_with_their_roles(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/permissions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Permissions/Index')
                ->has('permissions', 19)
                ->where('permissions.0.name', 'categories.manage')
                ->where('permissions.0.roles', ['admin'])
            );
    }
}
