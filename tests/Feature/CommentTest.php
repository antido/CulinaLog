<?php

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /** A well-formed guest cookie token (64 letters and digits). */
    private const GUEST_TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const OTHER_GUEST_TOKEN = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    public function test_member_can_comment_under_their_account(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/recipes/{$recipe->slug}/comments", [
                'body' => 'Made this last night.',
                // A member cannot pose under another name.
                'guest_name' => 'Somebody Else',
            ])
            ->assertSessionHasNoErrors();

        $comment = Comment::first();

        $this->assertSame($user->id, $comment->user_id);
        $this->assertNull($comment->guest_name);
        $this->assertNull($comment->guest_token_hash);
        $this->assertSame(CommentStatus::Visible, $comment->status);
        $this->assertSame($user->name, $comment->authorName());
    }

    public function test_guest_can_comment_and_is_shown_as_anonymous(): void
    {
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", ['body' => 'Looks tasty.'])
            ->assertSessionHasNoErrors()
            ->assertCookie('guest_token');

        $comment = Comment::first();

        $this->assertNull($comment->user_id);
        $this->assertSame('Anonymous', $comment->authorName());
        $this->assertSame(64, strlen($comment->guest_token_hash));

        $this->get("/recipes/{$recipe->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('comments.data.0.author_name', 'Anonymous')
                ->where('comments.data.0.is_guest', true)
                ->where('comments.data.0.author', null)
                ->missing('comments.data.0.guest_token_hash')
                ->where('commentCount', 1)
            );
    }

    public function test_guest_can_supply_a_display_name(): void
    {
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", [
            'body' => 'Great.',
            'guest_name' => 'Lola Nena',
        ]);

        $this->assertSame('Lola Nena', Comment::first()->authorName());
    }

    public function test_guest_token_is_stored_hashed_not_raw(): void
    {
        $recipe = Recipe::factory()->create();

        $this->withCookie('guest_token', self::GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'Hashed please.']);

        $comment = Comment::first();

        $this->assertNotSame(self::GUEST_TOKEN, $comment->guest_token_hash);
        $this->assertSame(hash('sha256', self::GUEST_TOKEN), $comment->guest_token_hash);
    }

    public function test_guest_comments_can_be_switched_off(): void
    {
        config(['community.guest_comments' => false]);
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", ['body' => 'Hello?'])
            ->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_comment_body_is_validated(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/recipes/{$recipe->slug}/comments", ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->actingAs($user)
            ->post("/recipes/{$recipe->slug}/comments", ['body' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_guest_name_is_limited_in_length(): void
    {
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", [
            'body' => 'Hi.',
            'guest_name' => str_repeat('n', 41),
        ])->assertSessionHasErrors('guest_name');
    }

    public function test_cannot_comment_on_a_recipe_that_is_not_public(): void
    {
        $draft = Recipe::factory()->draft()->create();
        $hidden = Recipe::factory()->hidden()->create();

        $this->post("/recipes/{$draft->slug}/comments", ['body' => 'Hi.'])->assertNotFound();
        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$hidden->slug}/comments", ['body' => 'Hi.'])
            ->assertNotFound();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_user_can_reply_to_a_comment(): void
    {
        $parent = Comment::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$parent->recipe->slug}/comments", [
                'body' => 'Agreed.',
                'parent_id' => $parent->id,
            ])
            ->assertSessionHasNoErrors();

        $reply = Comment::firstWhere('body', 'Agreed.');

        $this->assertSame($parent->id, $reply->parent_id);
        $this->assertSame(1, $reply->depth);

        $this->get("/recipes/{$parent->recipe->slug}")
            ->assertInertia(fn ($page) => $page
                ->has('comments.data', 1)
                ->where('comments.data.0.replies.0.body', 'Agreed.')
                ->where('commentCount', 2)
            );
    }

    public function test_replies_are_nested_at_most_three_levels(): void
    {
        $level0 = Comment::factory()->create();
        $level1 = Comment::factory()->replyTo($level0)->create();
        $level2 = Comment::factory()->replyTo($level1)->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$level0->recipe->slug}/comments", [
                'body' => 'A reply to the deepest comment.',
                'parent_id' => $level2->id,
            ]);

        $reply = Comment::firstWhere('body', 'A reply to the deepest comment.');

        // It sits next to the comment it answered, not one level deeper.
        $this->assertSame($level1->id, $reply->parent_id);
        $this->assertSame(2, $reply->depth);
    }

    public function test_reply_must_belong_to_the_same_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $commentElsewhere = Comment::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$recipe->slug}/comments", [
                'body' => 'Cross-posted reply.',
                'parent_id' => $commentElsewhere->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_cannot_reply_to_a_hidden_comment(): void
    {
        $hidden = Comment::factory()->hidden()->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$hidden->recipe->slug}/comments", [
                'body' => 'Replying to something hidden.',
                'parent_id' => $hidden->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_the_same_comment_cannot_be_posted_twice(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post("/recipes/{$recipe->slug}/comments", ['body' => 'Same words.']);
        $this->actingAs($user)
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'Same words.'])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_duplicate_prevention_applies_to_each_guest_separately(): void
    {
        $recipe = Recipe::factory()->create();

        $this->withCookie('guest_token', self::GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'Nice!']);
        $this->withCookie('guest_token', self::GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'Nice!'])
            ->assertSessionHasErrors('body');

        // A different guest may say the same thing.
        $this->withCookie('guest_token', self::OTHER_GUEST_TOKEN)
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'Nice!'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('comments', 2);
    }

    public function test_guest_comments_are_rate_limited(): void
    {
        $recipe = Recipe::factory()->create();

        foreach (['One.', 'Two.', 'Three.'] as $body) {
            $this->post("/recipes/{$recipe->slug}/comments", ['body' => $body])->assertRedirect();
        }

        $this->post("/recipes/{$recipe->slug}/comments", ['body' => 'Four.'])
            ->assertTooManyRequests();

        $this->assertDatabaseCount('comments', 3);
    }

    public function test_rate_limited_inertia_requests_get_a_message_instead_of_an_error_page(): void
    {
        $recipe = Recipe::factory()->create();

        foreach (['One.', 'Two.', 'Three.'] as $body) {
            $this->post("/recipes/{$recipe->slug}/comments", ['body' => $body]);
        }

        $this->from("/recipes/{$recipe->slug}")
            ->withHeader('X-Inertia', 'true')
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'Four.'])
            ->assertRedirect("/recipes/{$recipe->slug}")
            ->assertSessionHas('error');
    }

    public function test_guest_comment_with_a_link_waits_for_approval(): void
    {
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", [
            'body' => 'Cheap pills at https://spam.example',
        ]);

        $this->assertSame(CommentStatus::Pending, Comment::first()->status);

        $this->get("/recipes/{$recipe->slug}")
            ->assertInertia(fn ($page) => $page->has('comments.data', 0)->where('commentCount', 0));
    }

    public function test_member_comment_with_a_link_is_posted_directly(): void
    {
        $recipe = Recipe::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$recipe->slug}/comments", [
                'body' => 'I used this pan: https://example.com/pan',
            ]);

        $this->assertSame(CommentStatus::Visible, Comment::first()->status);
    }

    public function test_spam_trap_field_discards_the_comment_silently(): void
    {
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", [
            'body' => 'I am a bot.',
            'website' => 'https://bot.example',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_html_in_a_comment_is_escaped_on_the_page(): void
    {
        $recipe = Recipe::factory()->create();
        $payload = '<script>alert("xss")</script><img src=x onerror=alert(1)>';

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$recipe->slug}/comments", ['body' => $payload]);

        $response = $this->get("/recipes/{$recipe->slug}");

        // The text is kept exactly as typed and handed to React as data...
        $response->assertInertia(fn ($page) => $page->where('comments.data.0.body', $payload));

        // ...but it never appears in the HTML as a real tag.
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_html_in_a_guest_name_is_escaped_on_the_page(): void
    {
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", [
            'body' => 'Hello.',
            'guest_name' => '<b onmouseover=alert(1)>x</b>',
        ]);

        $this->get("/recipes/{$recipe->slug}")
            ->assertDontSee('<b onmouseover=alert(1)>x</b>', false);
    }

    public function test_status_and_owner_cannot_be_set_from_the_request(): void
    {
        $recipe = Recipe::factory()->create();
        $victim = User::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", [
            'body' => 'Visit www.spam.example now',
            'status' => 'visible',
            'user_id' => $victim->id,
            'depth' => 0,
        ]);

        $comment = Comment::first();

        $this->assertNull($comment->user_id);
        $this->assertSame(CommentStatus::Pending, $comment->status);
    }

    public function test_hidden_and_pending_comments_are_not_shown(): void
    {
        $recipe = Recipe::factory()->create();
        Comment::factory()->for($recipe)->create(['body' => 'Shown.']);
        Comment::factory()->for($recipe)->hidden()->create();
        Comment::factory()->for($recipe)->pending()->create();

        $this->get("/recipes/{$recipe->slug}")
            ->assertInertia(fn ($page) => $page
                ->has('comments.data', 1)
                ->where('comments.data.0.body', 'Shown.')
            );
    }

    public function test_member_can_delete_their_own_comment(): void
    {
        $comment = Comment::factory()->create();

        $this->actingAs($comment->user)
            ->delete("/comments/{$comment->id}")
            ->assertRedirect();

        $this->assertNull($comment->fresh());
    }

    public function test_member_cannot_delete_someone_elses_comment(): void
    {
        $comment = Comment::factory()->create();
        $guestComment = Comment::factory()->guest()->create();

        $intruder = User::factory()->create();

        $this->actingAs($intruder)->delete("/comments/{$comment->id}")->assertForbidden();
        $this->actingAs($intruder)->delete("/comments/{$guestComment->id}")->assertForbidden();

        $this->assertNotNull($comment->fresh());
        $this->assertNotNull($guestComment->fresh());
    }

    public function test_recipe_author_cannot_delete_comments_on_their_recipe(): void
    {
        $comment = Comment::factory()->create();

        $this->actingAs($comment->recipe->user)
            ->delete("/comments/{$comment->id}")
            ->assertForbidden();
    }

    public function test_guests_cannot_delete_comments(): void
    {
        $comment = Comment::factory()->guest()->create();

        $this->delete("/comments/{$comment->id}")->assertRedirect('/login');

        $this->assertNotNull($comment->fresh());
    }

    public function test_moderator_can_delete_any_comment(): void
    {
        $comment = Comment::factory()->guest()->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->delete("/comments/{$comment->id}")
            ->assertRedirect();

        $this->assertNull($comment->fresh());
    }

    public function test_delete_button_is_offered_only_to_people_who_may_delete(): void
    {
        $comment = Comment::factory()->create();
        $path = "/recipes/{$comment->recipe->slug}";

        $this->get($path)
            ->assertInertia(fn ($page) => $page->where('comments.data.0.can_delete', false));

        $this->actingAs(User::factory()->create())->get($path)
            ->assertInertia(fn ($page) => $page->where('comments.data.0.can_delete', false));

        $this->actingAs($comment->user)->get($path)
            ->assertInertia(fn ($page) => $page->where('comments.data.0.can_delete', true));
    }
}
