<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_list_shows_only_active_categories(): void
    {
        Category::factory()->create(['name' => 'Soups']);
        Category::factory()->inactive()->create(['name' => 'Hidden Things']);

        $this->get('/categories')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Categories/Index')
                ->has('categories', 1)
                ->where('categories.0.name', 'Soups')
            );
    }

    public function test_anyone_can_view_an_active_category(): void
    {
        $category = Category::factory()->create(['name' => 'Baking']);

        $this->get("/categories/{$category->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Categories/Show')
                ->where('category.name', 'Baking')
            );
    }

    public function test_disabled_category_is_not_found_for_visitors_and_users(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->get("/categories/{$category->slug}")->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get("/categories/{$category->slug}")
            ->assertNotFound();
    }

    public function test_admin_can_view_a_disabled_category(): void
    {
        $category = Category::factory()->inactive()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/categories/{$category->slug}")
            ->assertOk();
    }

    public function test_admin_can_create_a_category_with_an_image(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/categories', [
                'name' => 'Street Food',
                'description' => 'Eaten standing up.',
                'is_active' => true,
                'image' => UploadedFile::fake()->image('street.jpg', 2000, 1000),
            ])
            ->assertRedirect('/admin/categories')
            ->assertSessionHasNoErrors();

        $category = Category::firstWhere('name', 'Street Food');

        $this->assertSame('street-food', $category->slug);
        $this->assertTrue($category->is_active);
        $this->assertStringEndsWith('.webp', $category->image);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_slugs_are_unique(): void
    {
        $this->assertSame('pasta', Category::uniqueSlug('Pasta'));

        Category::factory()->create(['name' => 'Pasta']);

        $this->assertSame('pasta-2', Category::uniqueSlug('Pasta!'));
    }

    public function test_category_name_is_required_and_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['name' => 'Soups']);

        $this->actingAs($admin)
            ->post('/admin/categories', ['name' => '', 'is_active' => true])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post('/admin/categories', ['name' => 'Soups', 'is_active' => true])
            ->assertSessionHasErrors('name');
    }

    public function test_category_image_must_be_an_image(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/categories', [
                'name' => 'Bad Upload',
                'is_active' => true,
                'image' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('categories', ['name' => 'Bad Upload']);
    }

    public function test_admin_can_update_and_disable_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Soup']);
        $originalSlug = $category->slug;

        $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/categories/{$category->slug}", [
                'name' => 'Soups and stews',
                'description' => 'Updated.',
                'is_active' => false,
            ])
            ->assertRedirect('/admin/categories')
            ->assertSessionHasNoErrors();

        $category->refresh();

        $this->assertSame('Soups and stews', $category->name);
        $this->assertFalse($category->is_active);
        $this->assertSame($originalSlug, $category->slug);
    }

    public function test_slug_cannot_be_set_from_the_request(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/categories', [
                'name' => 'Grills',
                'slug' => 'admin',
                'is_active' => true,
            ]);

        $this->assertSame('grills', Category::firstWhere('name', 'Grills')->slug);
    }

    public function test_admin_can_delete_a_category_and_its_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Temporary',
            'is_active' => true,
            'image' => UploadedFile::fake()->image('temp.png'),
        ]);
        $category = Category::firstWhere('name', 'Temporary');

        $this->actingAs($admin)
            ->delete("/admin/categories/{$category->slug}")
            ->assertRedirect('/admin/categories');

        $this->assertNull($category->fresh());
        Storage::disk('public')->assertMissing($category->image);
    }

    public function test_users_and_moderators_cannot_manage_categories(): void
    {
        $category = Category::factory()->create(['name' => 'Protected']);

        foreach ([User::factory()->create(), User::factory()->moderator()->create()] as $user) {
            $this->actingAs($user)->get('/admin/categories')->assertForbidden();
            $this->actingAs($user)->get('/admin/categories/create')->assertForbidden();
            $this->actingAs($user)
                ->post('/admin/categories', ['name' => 'Nope', 'is_active' => true])
                ->assertForbidden();
            $this->actingAs($user)
                ->put("/admin/categories/{$category->slug}", ['name' => 'Changed', 'is_active' => true])
                ->assertForbidden();
            $this->actingAs($user)->delete("/admin/categories/{$category->slug}")->assertForbidden();
        }

        $this->assertDatabaseMissing('categories', ['name' => 'Nope']);
        $this->assertSame('Protected', $category->fresh()->name);
    }

    public function test_guests_cannot_manage_categories(): void
    {
        $this->get('/admin/categories')->assertRedirect('/login');
        $this->post('/admin/categories', ['name' => 'Nope'])->assertRedirect('/login');
    }
}
