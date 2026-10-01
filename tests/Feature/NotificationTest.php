<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\Recipe;
use App\Models\Report;
use App\Models\User;
use App\Notifications\CommentReplied;
use App\Notifications\ForumReplyPosted;
use App\Notifications\RecipeCommented;
use App\Notifications\RecipeReacted;
use App\Notifications\ReportReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipe_author_is_notified_of_a_comment(): void
    {
        Notification::fake();
        $recipe = Recipe::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'Lovely.']);

        Notification::assertSentTo($recipe->user, RecipeCommented::class);
        Notification::assertCount(1);
    }

    public function test_commenting_on_your_own_recipe_notifies_nobody(): void
    {
        Notification::fake();
        $recipe = Recipe::factory()->create();

        $this->actingAs($recipe->user)
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'A note from the author.']);

        Notification::assertNothingSent();
    }

    public function test_a_reply_notifies_the_comment_author_and_the_recipe_author_once_each(): void
    {
        Notification::fake();
        $comment = Comment::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$comment->recipe->slug}/comments", ['body' => 'Same here.', 'parent_id' => $comment->id]);

        Notification::assertSentTo($comment->user, CommentReplied::class);
        Notification::assertSentTo($comment->recipe->user, RecipeCommented::class);
        Notification::assertCount(2);
    }

    public function test_a_reply_to_the_recipe_authors_own_comment_sends_one_notification(): void
    {
        Notification::fake();
        $recipe = Recipe::factory()->create();
        $comment = Comment::factory()->for($recipe)->for($recipe->user)->create();

        $this->actingAs(User::factory()->create())
            ->post("/recipes/{$recipe->slug}/comments", ['body' => 'Thanks!', 'parent_id' => $comment->id]);

        Notification::assertSentTo($recipe->user, CommentReplied::class);
        Notification::assertCount(1);
    }

    public function test_a_guest_comment_held_for_review_notifies_only_after_approval(): void
    {
        Notification::fake();
        $recipe = Recipe::factory()->create();

        $this->post("/recipes/{$recipe->slug}/comments", ['body' => 'Look at https://spam.example']);
        Notification::assertNothingSent();

        $this->actingAs(User::factory()->moderator()->create())
            ->patch('/moderation/comments/'.Comment::first()->id, ['status' => 'visible']);

        Notification::assertSentTo($recipe->user, RecipeCommented::class);
    }

    public function test_recipe_author_is_notified_of_a_first_reaction_only(): void
    {
        Notification::fake();
        $recipe = Recipe::factory()->create();
        $fan = User::factory()->create();

        $this->actingAs($fan)->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like']);
        // Switching reactions must not notify again.
        $this->actingAs($fan)->post("/recipes/{$recipe->slug}/reactions", ['type' => 'love']);

        Notification::assertSentToTimes($recipe->user, RecipeReacted::class, 1);
    }

    public function test_reacting_to_your_own_recipe_notifies_nobody(): void
    {
        Notification::fake();
        $recipe = Recipe::factory()->create();

        $this->actingAs($recipe->user)->post("/recipes/{$recipe->slug}/reactions", ['type' => 'wow']);

        Notification::assertNothingSent();
    }

    public function test_topic_author_is_notified_of_a_reply(): void
    {
        Notification::fake();
        $topic = ForumTopic::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Here is my view.']);

        Notification::assertSentTo($topic->user, ForumReplyPosted::class);
        Notification::assertCount(1);
    }

    public function test_guest_forum_reply_notifies_only_after_approval(): void
    {
        Notification::fake();
        $topic = ForumTopic::factory()->create();

        $this->post("/forum/topics/{$topic->slug}/replies", ['body' => 'Guest view.']);
        Notification::assertNothingSent();

        $this->actingAs(User::factory()->moderator()->create())
            ->patch('/moderation/forum/replies/'.ForumReply::first()->id, ['status' => 'visible']);

        Notification::assertSentTo($topic->user, ForumReplyPosted::class);
    }

    public function test_reporter_is_notified_when_their_report_is_reviewed(): void
    {
        Notification::fake();
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->patch("/moderation/reports/{$report->id}", ['status' => 'resolved']);

        Notification::assertSentTo($report->reporter, ReportReviewed::class);
    }

    public function test_notifications_are_stored_and_listed_for_their_owner(): void
    {
        $recipe = Recipe::factory()->create(['title' => 'Noodle Soup']);
        $commenter = User::factory()->create(['name' => 'Kim']);

        $this->actingAs($commenter)->post("/recipes/{$recipe->slug}/comments", ['body' => 'Lovely.']);

        $this->actingAs($recipe->user)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Notifications/Index')
                ->has('notifications.data', 1)
                ->where('notifications.data.0.message', 'Kim commented on your recipe "Noodle Soup".')
                ->where('notifications.data.0.is_read', false)
                ->where('auth.unread_notifications', 1)
            );

        // The commenter has none.
        $this->actingAs($commenter)
            ->get('/notifications')
            ->assertInertia(fn ($page) => $page->has('notifications.data', 0)->where('auth.unread_notifications', 0));
    }

    public function test_opening_a_notification_marks_it_read_and_goes_to_the_content(): void
    {
        $recipe = Recipe::factory()->create();
        $this->actingAs(User::factory()->create())->post("/recipes/{$recipe->slug}/comments", ['body' => 'Lovely.']);

        $notification = $recipe->user->notifications()->first();

        $this->actingAs($recipe->user)
            ->get("/notifications/{$notification->id}")
            ->assertRedirect("/recipes/{$recipe->slug}");

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_open_someone_elses_notification(): void
    {
        $recipe = Recipe::factory()->create();
        $this->actingAs(User::factory()->create())->post("/recipes/{$recipe->slug}/comments", ['body' => 'Lovely.']);

        $notification = $recipe->user->notifications()->first();

        $this->actingAs(User::factory()->create())
            ->get("/notifications/{$notification->id}")
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read_only_affects_the_signed_in_user(): void
    {
        $recipe = Recipe::factory()->create();
        $otherRecipe = Recipe::factory()->create();
        $commenter = User::factory()->create();

        $this->actingAs($commenter)->post("/recipes/{$recipe->slug}/comments", ['body' => 'One.']);
        $this->actingAs($commenter)->post("/recipes/{$otherRecipe->slug}/comments", ['body' => 'Two.']);

        $this->actingAs($recipe->user)->post('/notifications/read-all')->assertRedirect();

        $this->assertSame(0, $recipe->user->unreadNotifications()->count());
        $this->assertSame(1, $otherRecipe->user->unreadNotifications()->count());
    }

    public function test_guests_cannot_see_notifications(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
        $this->post('/notifications/read-all')->assertRedirect('/login');
    }
}
