<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The fields the profile form always sends.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profileData(User $user, array $overrides = []): array
    {
        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            ...$overrides,
        ];
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_guests_cannot_open_the_profile_form(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->patch('/profile', ['name' => 'Nobody'])->assertRedirect('/login');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'username' => 'test_user',
                'bio' => 'I bake bread on weekends.',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test_user', $user->username);
        $this->assertSame('I bake bread on weekends.', $user->bio);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', $this->profileData($user, ['name' => 'Test User']));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_username_must_be_unique_and_well_formed(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['username' => 'taken_name']);

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, ['username' => $other->username]))
            ->assertSessionHasErrors('username');

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, ['username' => 'Bad Name!']))
            ->assertSessionHasErrors('username');

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, ['username' => 'ab']))
            ->assertSessionHasErrors('username');
    }

    public function test_bio_is_limited_to_500_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, ['bio' => str_repeat('a', 501)]))
            ->assertSessionHasErrors('bio');
    }

    public function test_user_can_upload_a_profile_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, [
                'avatar' => UploadedFile::fake()->image('me.jpg', 1200, 900),
            ]))
            ->assertSessionHasNoErrors();

        $path = $user->fresh()->avatar_path;

        // The image is re-saved as WebP under a random name, not the uploaded name.
        $this->assertNotNull($path);
        $this->assertStringStartsWith('avatars/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringNotContainsString('me', basename($path));
        Storage::disk('public')->assertExists($path);

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertLessThanOrEqual(512, max($width, $height));
    }

    public function test_uploading_a_new_image_deletes_the_old_one(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->profileData($user, [
            'avatar' => UploadedFile::fake()->image('first.png'),
        ]));
        $firstPath = $user->fresh()->avatar_path;

        $this->actingAs($user)->patch('/profile', $this->profileData($user, [
            'avatar' => UploadedFile::fake()->image('second.png'),
        ]));

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_user_can_remove_their_profile_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', $this->profileData($user, [
            'avatar' => UploadedFile::fake()->image('me.png'),
        ]));
        $path = $user->fresh()->avatar_path;

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, ['remove_avatar' => true]))
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, [
                'avatar' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ]))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_a_script_renamed_to_look_like_an_image_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $fake = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "hello"; ?>');

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, ['avatar' => $fake]))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_images_over_five_megabytes_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, [
                'avatar' => UploadedFile::fake()->image('huge.jpg')->size(5121),
            ]))
            ->assertSessionHasErrors('avatar');
    }

    public function test_avatar_path_cannot_be_set_directly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, [
                'avatar_path' => '../../.env',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_profile_update_only_changes_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['name' => 'Other Person']);

        $this->actingAs($user)
            ->patch('/profile', $this->profileData($user, [
                'id' => $other->id,
                'user_id' => $other->id,
                'name' => 'Changed Name',
            ]));

        $this->assertSame('Changed Name', $user->fresh()->name);
        $this->assertSame('Other Person', $other->fresh()->name);
    }

    public function test_public_profile_shows_only_intended_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Public Cook',
            'username' => 'public_cook',
            'bio' => 'Soup person.',
        ]);

        $this->get('/users/public_cook')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/Show')
                ->where('profile.name', 'Public Cook')
                ->where('profile.username', 'public_cook')
                ->where('profile.bio', 'Soup person.')
                ->has('profile.joined')
                ->has('profile.avatar_url')
                ->missing('profile.email')
                ->missing('profile.password')
                ->missing('profile.remember_token')
                ->missing('profile.email_verified_at')
                ->missing('profile.avatar_path')
            )
            ->assertDontSee($user->email);
    }

    public function test_public_profile_of_unknown_user_is_not_found(): void
    {
        $this->get('/users/nobody_here')->assertNotFound();
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
