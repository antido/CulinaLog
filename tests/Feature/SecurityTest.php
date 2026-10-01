<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /*
     * Security headers
     */

    public function test_pages_send_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');

        $policy = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        // Inline scripts are only allowed with the per-request nonce.
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]{20,}'/", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
    }

    public function test_the_script_nonce_changes_on_every_request(): void
    {
        $first = $this->get('/')->headers->get('Content-Security-Policy');
        $second = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertNotSame($first, $second);
    }

    public function test_error_pages_also_send_security_headers(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_https_requests_are_told_to_stay_on_https(): void
    {
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
        $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    }

    /*
     * Authentication and sessions
     */

    public function test_passwords_are_stored_hashed(): void
    {
        $this->post('/register', [
            'name' => 'Hash Me',
            'username' => 'hash_me',
            'email' => 'hash@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $stored = User::firstWhere('email', 'hash@example.com')->password;

        $this->assertNotSame('correct-horse-battery', $stored);
        $this->assertTrue(Hash::check('correct-horse-battery', $stored));
    }

    public function test_login_is_locked_after_five_wrong_passwords(): void
    {
        $user = User::factory()->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        // Even the right password is refused while the lock is active.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_session_id_changes_at_login(): void
    {
        $user = User::factory()->create();

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticated();
        $this->assertNotSame($before, session()->getId());
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $before = session()->getId();

        $this->post('/logout')->assertRedirect('/');

        $this->assertGuest();
        $this->assertNotSame($before, session()->getId());
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post('/register', ['name' => '', 'email' => 'not-an-email'])->assertSessionHasErrors();
        }

        $this->post('/register', ['name' => '', 'email' => 'not-an-email'])->assertTooManyRequests();
    }

    public function test_login_errors_do_not_reveal_whether_an_account_exists(): void
    {
        $user = User::factory()->create();

        $message = 'These credentials do not match our records.';

        // A wrong password and an unknown address get exactly the same answer.
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(302)
            ->assertSessionHasErrors(['email' => $message]);

        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'])
            ->assertStatus(302)
            ->assertSessionHasErrors(['email' => $message]);
    }

    public function test_session_cookies_are_hidden_from_javascript_and_same_site(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
    }

    public function test_the_guest_cookie_is_hidden_from_javascript(): void
    {
        $recipe = Recipe::factory()->create();

        $response = $this->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like']);

        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($cookie) => $cookie->getName() === 'guest_token');

        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_forms_are_protected_against_request_forgery(): void
    {
        // Tests skip the token check, so confirm the protection is installed
        // for every route in the "web" group.
        $this->assertContains(
            PreventRequestForgery::class,
            app('router')->getMiddlewareGroups()['web'],
        );
    }

    /*
     * Error pages
     */

    public function test_not_found_and_forbidden_show_a_friendly_page(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertInertia(fn ($page) => $page->component('Error')->where('status', 404));

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden()
            ->assertInertia(fn ($page) => $page->component('Error')->where('status', 403));
    }

    public function test_server_errors_never_show_technical_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        Route::get('/_test/boom', function (): never {
            throw new RuntimeException('database password is hunter2');
        });

        $this->get('/_test/boom')
            ->assertStatus(500)
            ->assertInertia(fn ($page) => $page->component('Error')->where('status', 500))
            ->assertDontSee('hunter2')
            ->assertDontSee('RuntimeException')
            ->assertDontSee('SecurityTest.php');
    }

    /*
     * File uploads
     */

    /**
     * @return array<string, mixed>
     */
    private function profileWith(User $user, UploadedFile $file): array
    {
        return ['name' => $user->name, 'username' => $user->username, 'email' => $user->email, 'avatar' => $file];
    }

    public function test_svg_gif_and_html_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $files = [
            UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->image('animation.gif'),
            UploadedFile::fake()->createWithContent('page.html', '<html><script>alert(1)</script></html>'),
            UploadedFile::fake()->create('archive.zip', 10, 'application/zip'),
        ];

        foreach ($files as $file) {
            $this->actingAs($user)
                ->patch('/profile', $this->profileWith($user, $file))
                ->assertSessionHasErrors('avatar');
        }

        $this->assertNull($user->fresh()->avatar_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_a_real_image_with_a_script_extension_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $image = UploadedFile::fake()->image('photo.jpg');
        $renamed = new UploadedFile($image->getRealPath(), 'photo.php', 'image/jpeg', null, true);

        $this->actingAs($user)
            ->patch('/profile', $this->profileWith($user, $renamed))
            ->assertSessionHasErrors('avatar');

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_stored_images_get_a_safe_random_name_whatever_was_uploaded(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $image = UploadedFile::fake()->image('photo.jpg');
        $trickyName = new UploadedFile($image->getRealPath(), '../../evil.php.jpg', 'image/jpeg', null, true);

        $this->actingAs($user)
            ->patch('/profile', $this->profileWith($user, $trickyName))
            ->assertSessionHasNoErrors();

        $path = $user->fresh()->avatar_path;

        $this->assertMatchesRegularExpression('#^avatars/[A-Za-z0-9]{40}\.webp$#', $path);
        $this->assertSame(['avatars/'.basename($path)], Storage::disk('public')->allFiles());

        // What is stored is a freshly encoded WebP image, not the uploaded bytes.
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring(Storage::disk('public')->get($path))[2]);
    }

    public function test_images_with_extreme_dimensions_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profileWith($user, UploadedFile::fake()->image('wide.png', 6001, 10)))
            ->assertSessionHasErrors('avatar');
    }

    /*
     * Information disclosure
     */

    public function test_public_pages_never_contain_email_addresses(): void
    {
        $author = User::factory()->create(['email' => 'private-author@example.com']);
        $recipe = Recipe::factory()->for($author)->create();
        Comment::factory()->for($recipe)->for($author)->create();
        $topic = ForumTopic::factory()->for($author)->create();
        ForumReply::factory()->for($topic, 'topic')->for($author)->create();

        $pages = [
            '/',
            '/recipes',
            "/recipes/{$recipe->slug}",
            "/categories/{$recipe->category->slug}",
            "/users/{$author->username}",
            '/forum',
            "/forum/topics/{$topic->slug}",
            '/search?q='.urlencode($recipe->title),
        ];

        foreach ($pages as $page) {
            $this->get($page)
                ->assertOk()
                ->assertDontSee('private-author@example.com')
                ->assertDontSee('remember_token')
                ->assertDontSee('guest_token_hash');
        }
    }

    public function test_signed_in_pages_only_contain_the_viewers_own_email(): void
    {
        $author = User::factory()->create(['email' => 'private-author@example.com']);
        $recipe = Recipe::factory()->for($author)->create();
        $viewer = User::factory()->create(['email' => 'viewer@example.com']);

        $this->actingAs($viewer)
            ->get("/recipes/{$recipe->slug}")
            ->assertSee('viewer@example.com')
            ->assertDontSee('private-author@example.com');
    }

    /*
     * Mass assignment
     */

    public function test_protected_columns_are_not_fillable(): void
    {
        $this->assertNotContains('avatar_path', (new User)->getFillable());
        $this->assertNotContains('email_verified_at', (new User)->getFillable());

        foreach (['user_id', 'slug', 'is_hidden', 'published_at', 'image'] as $column) {
            $this->assertNotContains($column, (new Recipe)->getFillable());
        }

        foreach (['user_id', 'status', 'recipe_id', 'parent_id', 'guest_token_hash'] as $column) {
            $this->assertNotContains($column, (new Comment)->getFillable());
        }

        foreach (['user_id', 'status', 'is_pinned', 'is_locked', 'views', 'slug'] as $column) {
            $this->assertNotContains($column, (new ForumTopic)->getFillable());
        }

        $this->assertNotContains('slug', (new Category)->getFillable());
    }

    public function test_a_user_cannot_verify_their_own_email_through_the_profile_form(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'email_verified_at' => now()->toDateTimeString(),
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
