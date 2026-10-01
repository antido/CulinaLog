<?php

namespace Tests\Feature;

use App\Enums\ForumStatus;
use App\Models\ForumCategory;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function topicData(array $overrides = []): array
    {
        return [
            'title' => 'How long should dough rest?',
            'category_id' => ForumCategory::factory()->create()->id,
            'body' => 'Recipes say anything from ten minutes to a day.',
            ...$overrides,
        ];
    }

    public function test_forum_home_lists_categories_and_visible_topics(): void
    {
        $category = ForumCategory::factory()->create(['name' => 'Techniques']);
        ForumCategory::factory()->inactive()->create(['name' => 'Secret']);
        ForumTopic::factory()->for($category, 'category')->create(['title' => 'Shown Topic']);
        ForumTopic::factory()->for($category, 'category')->hidden()->create(['title' => 'Hidden Topic']);

        $this->get('/forum')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Forum/Index')
                ->has('categories', 1)
                ->where('categories.0.name', 'Techniques')
                ->where('categories.0.topics_count', 1)
                ->has('topics.data', 1)
                ->where('topics.data.0.title', 'Shown Topic')
            );
    }

    public function test_pinned_topics_come_first_then_most_recently_active(): void
    {
        $category = ForumCategory::factory()->create();
        ForumTopic::factory()->for($category, 'category')->create(['title' => 'Old', 'last_activity_at' => now()->subDays(3)]);
        ForumTopic::factory()->for($category, 'category')->create(['title' => 'New', 'last_activity_at' => now()]);
        ForumTopic::factory()->for($category, 'category')->pinned()->create(['title' => 'Pinned', 'last_activity_at' => now()->subYear()]);

        $this->get("/forum/c/{$category->slug}")
            ->assertInertia(fn ($page) => $page
                ->component('Forum/Category')
                ->where('topics.data.0.title', 'Pinned')
                ->where('topics.data.1.title', 'New')
                ->where('topics.data.2.title', 'Old')
            );
    }

    public function test_topics_are_paginated(): void
    {
        $category = ForumCategory::factory()->create();
        ForumTopic::factory()->for($category, 'category')->count(16)->create();

        $this->get("/forum/c/{$category->slug}")
            ->assertInertia(fn ($page) => $page->has('topics.data', 15)->where('topics.last_page', 2));
    }

    public function test_disabled_forum_category_is_not_found(): void
    {
        $category = ForumCategory::factory()->inactive()->create();

        $this->get("/forum/c/{$category->slug}")->assertNotFound();
    }

    public function test_forum_search_matches_title_and_body(): void
    {
        ForumTopic::factory()->create(['title' => 'Sourdough starter help', 'body' => 'It smells odd.']);
        ForumTopic::factory()->create(['title' => 'Knife advice', 'body' => 'For slicing sourdough loaves.']);
        ForumTopic::factory()->create(['title' => 'Unrelated', 'body' => 'Nothing here.']);
        ForumTopic::factory()->hidden()->create(['title' => 'Hidden sourdough']);

        $this->get('/forum?q=sourdough')
            ->assertInertia(fn ($page) => $page->has('topics.data', 2)->where('filters.q', 'sourdough'));
    }

    public function test_forum_search_treats_wildcards_and_quotes_as_plain_text(): void
    {
        ForumTopic::factory()->count(2)->create();

        $this->get('/forum?q='.urlencode('%'))->assertOk()
            ->assertInertia(fn ($page) => $page->has('topics.data', 0));

        $this->get('/forum?q='.urlencode("' OR 1=1 --"))->assertOk()
            ->assertInertia(fn ($page) => $page->has('topics.data', 0));
    }

    public function test_member_can_start_a_topic(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/forum/topics', $this->topicData([
                'user_id' => 999,
                'is_pinned' => true,
                'is_locked' => true,
                'status' => 'hidden',
                'views' => 5000,
                'slug' => 'chosen',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/forum/topics/how-long-should-dough-rest');

        $topic = ForumTopic::first();

        // Only title, category and body come from the form.
        $this->assertSame($user->id, $topic->user_id);
        $this->assertFalse($topic->is_pinned);
        $this->assertFalse($topic->is_locked);
        $this->assertSame(ForumStatus::Visible, $topic->status);
        $this->assertSame(0, $topic->views);
        $this->assertNotNull($topic->last_activity_at);
    }

    public function test_guests_and_unverified_users_cannot_start_topics(): void
    {
        $this->get('/forum/topics/create')->assertRedirect('/login');
        $this->post('/forum/topics', $this->topicData())->assertRedirect('/login');

        $this->actingAs(User::factory()->unverified()->create())
            ->post('/forum/topics', $this->topicData())
            ->assertRedirect('/verify-email');

        $this->assertDatabaseCount('forum_topics', 0);
    }

    public function test_topic_fields_are_validated(): void
    {
        $inactive = ForumCategory::factory()->inactive()->create();

        $this->actingAs(User::factory()->create())
            ->post('/forum/topics', ['title' => 'Hey', 'category_id' => $inactive->id, 'body' => 'short'])
            ->assertSessionHasErrors(['title', 'category_id', 'body']);
    }

    public function test_anyone_can_read_a_topic_and_views_count_once_per_session(): void
    {
        $topic = ForumTopic::factory()->create(['title' => 'Readable']);
        $updatedAt = $topic->updated_at;

        $this->get("/forum/topics/{$topic->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Forum/Topics/Show')
                ->where('topic.title', 'Readable')
                ->where('can.update', false)
                ->where('can.moderate', false)
                ->where('can.reply', true)
                ->missing('topic.author.email')
            );
        $this->get("/forum/topics/{$topic->slug}");

        $this->assertSame(1, $topic->fresh()->views);
        $this->assertTrue($updatedAt->equalTo($topic->fresh()->updated_at));
    }

    public function test_hidden_topic_is_only_for_its_author_and_moderators(): void
    {
        $topic = ForumTopic::factory()->hidden()->create();

        $this->get("/forum/topics/{$topic->slug}")->assertNotFound();
        $this->actingAs(User::factory()->create())->get("/forum/topics/{$topic->slug}")->assertNotFound();
        $this->actingAs($topic->user)->get("/forum/topics/{$topic->slug}")->assertOk();
        $this->actingAs(User::factory()->moderator()->create())->get("/forum/topics/{$topic->slug}")->assertOk();
    }

    public function test_author_can_edit_their_topic(): void
    {
        $topic = ForumTopic::factory()->create();

        $this->actingAs($topic->user)
            ->put("/forum/topics/{$topic->slug}", $this->topicData([
                'title' => 'An improved title',
                'category_id' => $topic->category_id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('An improved title', $topic->fresh()->title);
    }

    public function test_user_cannot_edit_or_delete_another_users_topic(): void
    {
        $topic = ForumTopic::factory()->create(['title' => 'Original title']);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get("/forum/topics/{$topic->slug}/edit")->assertForbidden();
        $this->actingAs($intruder)
            ->put("/forum/topics/{$topic->slug}", $this->topicData(['title' => 'Hijacked title']))
            ->assertForbidden();
        $this->actingAs($intruder)->delete("/forum/topics/{$topic->slug}")->assertForbidden();

        $this->assertSame('Original title', $topic->fresh()->title);
    }

    public function test_author_cannot_edit_a_locked_topic(): void
    {
        $topic = ForumTopic::factory()->locked()->create();

        $this->actingAs($topic->user)
            ->put("/forum/topics/{$topic->slug}", $this->topicData(['category_id' => $topic->category_id]))
            ->assertForbidden();
    }

    public function test_author_can_delete_their_topic_with_its_replies(): void
    {
        $topic = ForumTopic::factory()->create();
        ForumReply::factory()->for($topic, 'topic')->count(2)->create();

        $this->actingAs($topic->user)
            ->delete("/forum/topics/{$topic->slug}")
            ->assertRedirect("/forum/c/{$topic->category->slug}");

        $this->assertDatabaseCount('forum_topics', 0);
        $this->assertDatabaseCount('forum_replies', 0);
    }

    public function test_member_reply_appears_at_once_and_bumps_the_topic(): void
    {
        $topic = ForumTopic::factory()->create(['last_activity_at' => now()->subDays(2)]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Overnight in the fridge.'])
            ->assertSessionHasNoErrors();

        $reply = ForumReply::first();

        $this->assertSame($user->id, $reply->user_id);
        $this->assertSame(ForumStatus::Visible, $reply->status);
        $this->assertTrue($topic->fresh()->last_activity_at->isToday());

        $this->get("/forum/topics/{$topic->slug}")
            ->assertInertia(fn ($page) => $page
                ->has('replies.data', 1)
                ->where('topic.replies_count', 1)
            );
    }

    public function test_replies_can_be_nested(): void
    {
        $parent = ForumReply::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/forum/topics/{$parent->topic->slug}/replies", [
                'body' => 'Answering you directly.',
                'parent_id' => $parent->id,
            ]);

        $this->get("/forum/topics/{$parent->topic->slug}")
            ->assertInertia(fn ($page) => $page
                ->has('replies.data', 1)
                ->where('replies.data.0.replies.0.body', 'Answering you directly.')
            );
    }

    public function test_nested_reply_must_belong_to_the_same_topic(): void
    {
        $topic = ForumTopic::factory()->create();
        $elsewhere = ForumReply::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Misplaced.', 'parent_id' => $elsewhere->id])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_guest_reply_is_anonymous_and_waits_for_approval(): void
    {
        $topic = ForumTopic::factory()->create(['last_activity_at' => now()->subDays(2)]);

        $this->post("/forum/topics/{$topic->slug}/replies", ['body' => 'A guest opinion.'])
            ->assertSessionHasNoErrors()
            ->assertCookie('guest_token');

        $reply = ForumReply::first();

        $this->assertNull($reply->user_id);
        $this->assertSame(ForumStatus::Pending, $reply->status);
        $this->assertSame('Anonymous', $reply->authorName());
        $this->assertSame(64, strlen($reply->guest_token_hash));

        // Not shown, not counted, and the topic is not bumped until approved.
        $this->assertFalse($topic->fresh()->last_activity_at->isToday());
        $this->get("/forum/topics/{$topic->slug}")
            ->assertInertia(fn ($page) => $page->has('replies.data', 0)->where('topic.replies_count', 0));
    }

    public function test_approved_guest_reply_is_shown_as_anonymous(): void
    {
        $reply = ForumReply::factory()->guest()->pending()->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->patch("/moderation/forum/replies/{$reply->id}", ['status' => 'visible'])
            ->assertSessionHasNoErrors();

        $this->app['auth']->forgetGuards();

        $this->get("/forum/topics/{$reply->topic->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('replies.data.0.author_name', 'Anonymous')
                ->where('replies.data.0.is_guest', true)
                ->missing('replies.data.0.guest_token_hash')
            );
    }

    public function test_guest_replies_can_be_switched_off(): void
    {
        config(['community.guest_forum_replies' => false]);
        $topic = ForumTopic::factory()->create();

        $this->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Hello?'])->assertForbidden();

        $this->get("/forum/topics/{$topic->slug}")
            ->assertInertia(fn ($page) => $page->where('can.reply', false));
    }

    public function test_guest_replies_have_a_stricter_rate_limit(): void
    {
        $topic = ForumTopic::factory()->create();

        $this->post("/forum/topics/{$topic->slug}/replies", ['body' => 'One.'])->assertRedirect();
        $this->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Two.'])->assertRedirect();
        $this->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Three.'])->assertTooManyRequests();

        $this->assertDatabaseCount('forum_replies', 2);
    }

    public function test_nobody_but_moderators_can_reply_to_a_locked_topic(): void
    {
        $topic = ForumTopic::factory()->locked()->create();

        $this->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Guest.'])->assertForbidden();
        $this->actingAs(User::factory()->create())
            ->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Member.'])
            ->assertForbidden();
        $this->actingAs($topic->user)
            ->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Author.'])
            ->assertForbidden();

        $this->actingAs(User::factory()->moderator()->create())
            ->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Moderator note.'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('forum_replies', 1);
    }

    public function test_cannot_reply_to_a_hidden_topic(): void
    {
        $topic = ForumTopic::factory()->hidden()->create();

        $this->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Hello.'])->assertNotFound();
    }

    public function test_duplicate_replies_and_spam_trap(): void
    {
        $topic = ForumTopic::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Same.']);
        $this->actingAs($user)
            ->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Same.'])
            ->assertSessionHasErrors('body');

        $this->actingAs($user)
            ->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Bot text.', 'website' => 'x'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('forum_replies', 1);
    }

    public function test_html_in_topics_and_replies_is_escaped(): void
    {
        $payload = '<script>alert("forum")</script>';
        $topic = ForumTopic::factory()->create(['body' => $payload]);
        ForumReply::factory()->for($topic, 'topic')->create(['body' => $payload]);

        $this->get("/forum/topics/{$topic->slug}")
            ->assertInertia(fn ($page) => $page->where('topic.body', $payload))
            ->assertDontSee($payload, false);
    }

    public function test_reply_ownership_is_enforced_on_delete(): void
    {
        $reply = ForumReply::factory()->create();
        $guestReply = ForumReply::factory()->guest()->create();
        $intruder = User::factory()->create();

        $this->delete("/forum/replies/{$reply->id}")->assertRedirect('/login');
        $this->actingAs($intruder)->delete("/forum/replies/{$reply->id}")->assertForbidden();
        $this->actingAs($intruder)->delete("/forum/replies/{$guestReply->id}")->assertForbidden();
        // Moderators hide; deleting other people's replies is for forum managers.
        $this->actingAs(User::factory()->moderator()->create())
            ->delete("/forum/replies/{$reply->id}")
            ->assertForbidden();

        $this->actingAs($reply->user)->delete("/forum/replies/{$reply->id}")->assertRedirect();
        $this->assertNull($reply->fresh());

        $this->actingAs(User::factory()->admin()->create())->delete("/forum/replies/{$guestReply->id}");
        $this->assertNull($guestReply->fresh());
    }

    public function test_moderator_can_pin_lock_and_hide_topics(): void
    {
        $topic = ForumTopic::factory()->create();
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)->patch("/moderation/forum/topics/{$topic->slug}", ['is_pinned' => true]);
        $this->assertTrue($topic->fresh()->is_pinned);
        $this->assertFalse($topic->fresh()->is_locked);

        $this->actingAs($moderator)->patch("/moderation/forum/topics/{$topic->slug}", ['is_locked' => true]);
        $this->assertTrue($topic->fresh()->is_locked);

        $this->actingAs($moderator)->patch("/moderation/forum/topics/{$topic->slug}", ['status' => 'hidden']);
        $this->assertSame(ForumStatus::Hidden, $topic->fresh()->status);

        $this->actingAs($moderator)->patch("/moderation/forum/topics/{$topic->slug}", [
            'status' => 'visible', 'is_pinned' => false, 'is_locked' => false,
        ]);
        $topic->refresh();
        $this->assertSame(ForumStatus::Visible, $topic->status);
        $this->assertFalse($topic->is_pinned);
        $this->assertFalse($topic->is_locked);
    }

    public function test_moderation_request_cannot_change_other_topic_fields(): void
    {
        $topic = ForumTopic::factory()->create(['title' => 'Kept title']);
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)->patch("/moderation/forum/topics/{$topic->slug}", [
            'is_pinned' => true,
            'title' => 'Changed title',
            'user_id' => $moderator->id,
            'views' => 99999,
        ]);

        $topic->refresh();

        $this->assertSame('Kept title', $topic->title);
        $this->assertNotSame($moderator->id, $topic->user_id);
        $this->assertSame(0, $topic->views);

        $this->actingAs($moderator)
            ->patch("/moderation/forum/topics/{$topic->slug}", ['status' => 'pending'])
            ->assertSessionHasErrors('status');
    }

    public function test_users_and_topic_authors_cannot_use_moderation_actions(): void
    {
        $topic = ForumTopic::factory()->create();
        $reply = ForumReply::factory()->pending()->create();

        $this->patch("/moderation/forum/topics/{$topic->slug}", ['is_pinned' => true])->assertRedirect('/login');

        $this->actingAs($topic->user)
            ->patch("/moderation/forum/topics/{$topic->slug}", ['is_pinned' => true])
            ->assertForbidden();
        $this->actingAs(User::factory()->create())
            ->patch("/moderation/forum/replies/{$reply->id}", ['status' => 'visible'])
            ->assertForbidden();

        $this->assertFalse($topic->fresh()->is_pinned);
        $this->assertSame(ForumStatus::Pending, $reply->fresh()->status);
    }

    public function test_admin_can_manage_forum_categories(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/forum-categories', [
                'name' => 'Fermentation', 'description' => 'Bubbles.', 'is_active' => true, 'sort_order' => 3,
            ])
            ->assertRedirect('/admin/forum-categories');

        $category = ForumCategory::firstWhere('name', 'Fermentation');
        $this->assertSame('fermentation', $category->slug);

        $this->actingAs($admin)
            ->put("/admin/forum-categories/{$category->slug}", [
                'name' => 'Fermentation', 'description' => '', 'is_active' => false, 'sort_order' => 9,
            ])
            ->assertSessionHasNoErrors();
        $this->assertFalse($category->fresh()->is_active);

        ForumTopic::factory()->for($category, 'category')->create();
        $this->actingAs($admin)
            ->delete("/admin/forum-categories/{$category->slug}")
            ->assertSessionHas('error');
        $this->assertNotNull($category->fresh());
    }

    public function test_only_admins_can_manage_forum_categories(): void
    {
        $category = ForumCategory::factory()->create();

        foreach ([User::factory()->create(), User::factory()->moderator()->create()] as $user) {
            $this->actingAs($user)->get('/admin/forum-categories')->assertForbidden();
            $this->actingAs($user)
                ->post('/admin/forum-categories', ['name' => 'Nope', 'is_active' => true, 'sort_order' => 1])
                ->assertForbidden();
            $this->actingAs($user)->delete("/admin/forum-categories/{$category->slug}")->assertForbidden();
        }

        $this->assertDatabaseMissing('forum_categories', ['name' => 'Nope']);
    }

    public function test_profile_shows_forum_activity(): void
    {
        $user = User::factory()->create();
        ForumTopic::factory()->for($user)->create();
        ForumTopic::factory()->for($user)->hidden()->create();
        ForumReply::factory()->for($user)->count(2)->create();

        $this->get("/users/{$user->username}")
            ->assertInertia(fn ($page) => $page
                ->where('stats.2', ['label' => 'Forum topics', 'value' => 1])
                ->where('stats.3', ['label' => 'Forum replies', 'value' => 2])
            );
    }
}
