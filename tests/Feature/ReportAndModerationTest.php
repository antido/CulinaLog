<?php

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Enums\ForumStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Comment;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\ModerationLog;
use App\Models\Recipe;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_report_each_kind_of_content(): void
    {
        $user = User::factory()->create();

        $targets = [
            'recipe' => Recipe::factory()->create(),
            'comment' => Comment::factory()->create(),
            'forum_topic' => ForumTopic::factory()->create(),
            'forum_reply' => ForumReply::factory()->create(),
        ];

        foreach ($targets as $type => $content) {
            $this->actingAs($user)
                ->post('/reports', ['type' => $type, 'id' => $content->id, 'reason' => 'spam'])
                ->assertSessionHasNoErrors()
                ->assertSessionHas('success');

            $this->assertDatabaseHas('reports', [
                'user_id' => $user->id,
                'reportable_type' => $type,
                'reportable_id' => $content->id,
                'status' => 'pending',
            ]);
        }

        $this->assertTrue(Report::first()->reportable->is($targets['recipe']));
    }

    public function test_guest_can_report_without_an_account(): void
    {
        $recipe = Recipe::factory()->create();

        $this->post('/reports', [
            'type' => 'recipe', 'id' => $recipe->id, 'reason' => 'copyright', 'description' => 'Copied from my blog.',
        ])->assertSessionHasNoErrors();

        $report = Report::first();

        $this->assertNull($report->user_id);
        $this->assertSame(ReportReason::CopyrightConcern, $report->reason);
        $this->assertSame(64, strlen($report->guest_token_hash));
    }

    public function test_report_fields_are_validated(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/reports', ['type' => 'user', 'id' => 1, 'reason' => 'spam'])
            ->assertSessionHasErrors('type');

        $this->actingAs($user)
            ->post('/reports', ['type' => 'App\\Models\\User', 'id' => 1, 'reason' => 'spam'])
            ->assertSessionHasErrors('type');

        $this->actingAs($user)
            ->post('/reports', ['type' => 'recipe', 'id' => $recipe->id, 'reason' => 'boring'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($user)
            ->post('/reports', ['type' => 'recipe', 'id' => $recipe->id, 'reason' => 'other'])
            ->assertSessionHasErrors('description');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_content_that_is_not_public_cannot_be_reported(): void
    {
        $user = User::factory()->create();

        $hidden = [
            'recipe' => Recipe::factory()->draft()->create(),
            'comment' => Comment::factory()->hidden()->create(),
            'forum_topic' => ForumTopic::factory()->hidden()->create(),
            'forum_reply' => ForumReply::factory()->pending()->create(),
        ];

        foreach ($hidden as $type => $content) {
            $this->actingAs($user)
                ->post('/reports', ['type' => $type, 'id' => $content->id, 'reason' => 'spam'])
                ->assertNotFound();
        }

        $this->actingAs($user)
            ->post('/reports', ['type' => 'recipe', 'id' => 987654, 'reason' => 'spam'])
            ->assertNotFound();

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_reporting_the_same_thing_twice_creates_one_report(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/reports', ['type' => 'recipe', 'id' => $recipe->id, 'reason' => 'spam']);
        $this->actingAs($user)->post('/reports', ['type' => 'recipe', 'id' => $recipe->id, 'reason' => 'harassment']);

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_report_status_and_reviewer_cannot_be_set_by_the_reporter(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/reports', [
            'type' => 'recipe', 'id' => $recipe->id, 'reason' => 'spam',
            'status' => 'resolved', 'reviewed_by' => $user->id, 'user_id' => 999,
        ]);

        $report = Report::first();

        $this->assertSame(ReportStatus::Pending, $report->status);
        $this->assertNull($report->reviewed_by);
        $this->assertSame($user->id, $report->user_id);
    }

    public function test_guest_reports_are_rate_limited(): void
    {
        $recipes = Recipe::factory()->count(4)->create();

        foreach ($recipes->take(3) as $recipe) {
            $this->post('/reports', ['type' => 'recipe', 'id' => $recipe->id, 'reason' => 'spam'])->assertRedirect();
        }

        $this->post('/reports', ['type' => 'recipe', 'id' => $recipes[3]->id, 'reason' => 'spam'])
            ->assertTooManyRequests();
    }

    public function test_moderator_sees_pending_reports_with_their_targets(): void
    {
        $comment = Comment::factory()->create(['body' => 'Buy my stuff']);
        Report::factory()->about($comment)->create(['description' => 'Advertising']);
        Report::factory()->resolved()->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->get('/moderation/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Moderation/Reports/Index')
                ->has('reports.data', 1)
                ->where('reports.data.0.target.type', 'Comment')
                ->where('reports.data.0.target.excerpt', 'Buy my stuff')
                ->where('reports.data.0.description', 'Advertising')
                ->missing('reports.data.0.guest_token_hash')
            );
    }

    public function test_report_list_can_be_filtered_by_status(): void
    {
        Report::factory()->create();
        Report::factory()->resolved()->count(2)->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->get('/moderation/reports?status=resolved')
            ->assertInertia(fn ($page) => $page->has('reports.data', 2)->where('status', 'resolved'));
    }

    public function test_report_list_survives_deleted_content(): void
    {
        $recipe = Recipe::factory()->create();
        Report::factory()->about($recipe)->create();
        $recipe->delete();

        $this->actingAs(User::factory()->moderator()->create())
            ->get('/moderation/reports')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('reports.data.0.target', null));
    }

    public function test_moderator_can_resolve_a_report_and_hide_the_content(): void
    {
        $comment = Comment::factory()->create();
        $report = Report::factory()->about($comment)->create();
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)
            ->patch("/moderation/reports/{$report->id}", ['status' => 'resolved', 'hide_content' => true])
            ->assertSessionHasNoErrors();

        $report->refresh();

        $this->assertSame(ReportStatus::Resolved, $report->status);
        $this->assertSame($moderator->id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);
        $this->assertSame(CommentStatus::Hidden, $comment->fresh()->status);

        $this->assertSame(
            ['hid', 'resolved a report about'],
            ModerationLog::orderBy('id')->pluck('action')->all(),
        );
    }

    public function test_moderator_can_dismiss_a_report_without_touching_the_content(): void
    {
        $recipe = Recipe::factory()->create();
        $report = Report::factory()->about($recipe)->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->patch("/moderation/reports/{$report->id}", ['status' => 'dismissed']);

        $this->assertSame(ReportStatus::Dismissed, $report->fresh()->status);
        $this->assertFalse($recipe->fresh()->is_hidden);
    }

    public function test_review_status_must_be_resolved_or_dismissed(): void
    {
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->moderator()->create())
            ->patch("/moderation/reports/{$report->id}", ['status' => 'pending'])
            ->assertSessionHasErrors('status');
    }

    public function test_normal_users_cannot_reach_any_moderation_action_by_url(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->create();
        $comment = Comment::factory()->pending()->create();
        $recipe = Recipe::factory()->create();
        $topic = ForumTopic::factory()->create();
        $reply = ForumReply::factory()->pending()->create();

        $attempts = [
            ['get', '/moderation', []],
            ['get', '/moderation/reports', []],
            ['get', '/moderation/pending', []],
            ['patch', "/moderation/reports/{$report->id}", ['status' => 'resolved', 'hide_content' => true]],
            ['patch', "/moderation/comments/{$comment->id}", ['status' => 'visible']],
            ['patch', "/moderation/recipes/{$recipe->slug}/hide", []],
            ['patch', "/moderation/recipes/{$recipe->slug}/restore", []],
            ['patch', "/moderation/forum/topics/{$topic->slug}", ['is_locked' => true]],
            ['patch', "/moderation/forum/replies/{$reply->id}", ['status' => 'visible']],
        ];

        foreach ($attempts as [$method, $url, $data]) {
            $this->actingAs($user)->{$method}($url, $data)->assertForbidden();
        }

        $this->assertSame(ReportStatus::Pending, $report->fresh()->status);
        $this->assertSame(CommentStatus::Pending, $comment->fresh()->status);
        $this->assertFalse($recipe->fresh()->is_hidden);
        $this->assertFalse($topic->fresh()->is_locked);
        $this->assertSame(ForumStatus::Pending, $reply->fresh()->status);
        $this->assertDatabaseCount('moderation_logs', 0);
    }

    public function test_guests_are_sent_to_login_from_moderation_actions(): void
    {
        $report = Report::factory()->create();

        $this->get('/moderation/reports')->assertRedirect('/login');
        $this->patch("/moderation/reports/{$report->id}", ['status' => 'resolved'])->assertRedirect('/login');
    }

    public function test_moderation_follows_the_specific_permission(): void
    {
        // Someone who may review reports but not moderate comments.
        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo(['reports.review']);

        $comment = Comment::factory()->create();
        $report = Report::factory()->about($comment)->create();

        $this->actingAs($reviewer)
            ->patch("/moderation/reports/{$report->id}", ['status' => 'resolved', 'hide_content' => true])
            ->assertForbidden();

        $this->assertSame(CommentStatus::Visible, $comment->fresh()->status);
        $this->assertSame(ReportStatus::Pending, $report->fresh()->status);

        // Resolving without hiding is within their permission.
        $this->actingAs($reviewer)
            ->patch("/moderation/reports/{$report->id}", ['status' => 'resolved'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ReportStatus::Resolved, $report->fresh()->status);
    }

    public function test_moderator_can_approve_and_hide_comments(): void
    {
        $pending = Comment::factory()->guest()->pending()->create();
        $visible = Comment::factory()->create();
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)->patch("/moderation/comments/{$pending->id}", ['status' => 'visible']);
        $this->actingAs($moderator)->patch("/moderation/comments/{$visible->id}", ['status' => 'hidden']);

        $this->assertSame(CommentStatus::Visible, $pending->fresh()->status);
        $this->assertSame(CommentStatus::Hidden, $visible->fresh()->status);

        $this->actingAs($moderator)
            ->patch("/moderation/comments/{$visible->id}", ['status' => 'pending'])
            ->assertSessionHasErrors('status');
    }

    public function test_pending_queue_lists_guest_comments_and_replies(): void
    {
        Comment::factory()->guest('Spammer')->pending()->create(['body' => 'See www.spam.example']);
        Comment::factory()->create();
        ForumReply::factory()->guest()->pending()->create(['body' => 'Guest opinion']);

        $this->actingAs(User::factory()->moderator()->create())
            ->get('/moderation/pending')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Moderation/Pending')
                ->has('comments', 1)
                ->where('comments.0.author_name', 'Spammer')
                ->has('replies', 1)
                ->where('replies.0.author_name', 'Anonymous')
            );
    }

    public function test_admin_can_use_every_moderation_action(): void
    {
        $admin = User::factory()->admin()->create();
        $report = Report::factory()->create();
        $recipe = Recipe::factory()->create();
        $topic = ForumTopic::factory()->create();

        $this->actingAs($admin)->get('/moderation/reports')->assertOk();
        $this->actingAs($admin)->patch("/moderation/reports/{$report->id}", ['status' => 'dismissed']);
        $this->actingAs($admin)->patch("/moderation/recipes/{$recipe->slug}/hide");
        $this->actingAs($admin)->patch("/moderation/forum/topics/{$topic->slug}", ['is_pinned' => true, 'is_locked' => true]);

        $this->assertSame(ReportStatus::Dismissed, $report->fresh()->status);
        $this->assertTrue($recipe->fresh()->is_hidden);
        $this->assertTrue($topic->fresh()->is_pinned);
        $this->assertTrue($topic->fresh()->is_locked);
    }

    public function test_moderation_actions_are_logged_with_a_readable_label(): void
    {
        $recipe = Recipe::factory()->create(['title' => 'Suspicious Stew']);
        $moderator = User::factory()->moderator()->create();

        $this->actingAs($moderator)->patch("/moderation/recipes/{$recipe->slug}/hide");

        $log = ModerationLog::first();

        $this->assertSame($moderator->id, $log->user_id);
        $this->assertSame('hid', $log->action);
        $this->assertSame('recipe', $log->subject_type);
        $this->assertSame('Recipe "Suspicious Stew"', $log->subject_label);
    }
}
