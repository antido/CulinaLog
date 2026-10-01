<?php

namespace App\Http\Controllers;

use App\Enums\ForumStatus;
use App\Http\Requests\StoreForumTopicRequest;
use App\Http\Requests\UpdateForumTopicRequest;
use App\Models\ForumCategory;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ForumTopicController extends Controller
{
    /**
     * Show the form for a new topic.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', ForumTopic::class);

        return Inertia::render('Forum/Topics/Create', [
            'categories' => ForumCategory::query()->active()->get(['id', 'name', 'slug']),
            // Pre-select the category the person came from, if any.
            'selectedCategory' => $request->query('category'),
        ]);
    }

    /**
     * Save a new topic. The author is always the signed-in user.
     */
    public function store(StoreForumTopicRequest $request): RedirectResponse
    {
        $topic = new ForumTopic($request->validated());
        $topic->user_id = $request->user()->id;
        $topic->slug = ForumTopic::uniqueSlug($topic->title);
        $topic->status = ForumStatus::Visible;
        $topic->last_activity_at = now();
        $topic->save();

        return redirect()
            ->route('forum.topics.show', $topic)
            ->with('success', 'Your topic was posted.');
    }

    /**
     * Show a topic with its replies.
     */
    public function show(Request $request, ForumTopic $topic, SeoService $seo): Response
    {
        abort_unless(Gate::allows('view', $topic), 404);

        $this->countView($request, $topic);

        $topic->load(['user', 'category'])->loadCount('visibleReplies');
        $user = $request->user();

        return Inertia::render('Forum/Topics/Show', [
            'topic' => [
                ...$topic->toCard(),
                'body' => $topic->body,
                'posted' => $topic->created_at->toFormattedDateString(),
            ],
            // Top-level replies are paginated, oldest first, each with its nested replies.
            'replies' => $topic->replies()
                ->visible()
                ->whereNull('parent_id')
                ->with(['user', 'replies.user', 'replies.replies.user'])
                ->oldest()
                ->paginate(15)
                ->through(fn (ForumReply $reply) => $reply->toThread($user)),
            'can' => [
                'update' => $user?->can('update', $topic) ?? false,
                'delete' => $user?->can('delete', $topic) ?? false,
                'moderate' => $user?->can('moderate', $topic) ?? false,
                'reply' => Gate::allows('reply', $topic),
            ],
        ])->withViewData('meta', [
            ...$seo->forPage($topic->title, $topic->body),
            'type' => 'article',
            'noindex' => ! $topic->isVisible(),
        ]);
    }

    /**
     * Show the form for editing a topic.
     */
    public function edit(ForumTopic $topic): Response
    {
        $this->authorize('update', $topic);

        return Inertia::render('Forum/Topics/Edit', [
            'topic' => [
                'slug' => $topic->slug,
                'title' => $topic->title,
                'category_id' => $topic->category_id,
                'body' => $topic->body,
            ],
            'categories' => ForumCategory::query()->active()->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Save changes to a topic. The slug stays the same so links keep working.
     */
    public function update(UpdateForumTopicRequest $request, ForumTopic $topic): RedirectResponse
    {
        $topic->update($request->validated());

        return redirect()
            ->route('forum.topics.show', $topic)
            ->with('success', 'Your topic was updated.');
    }

    /**
     * Delete a topic and its replies.
     */
    public function destroy(ForumTopic $topic): RedirectResponse
    {
        $this->authorize('delete', $topic);

        $categorySlug = $topic->category->slug;
        $topic->delete();

        return redirect()
            ->route('forum.categories.show', $categorySlug)
            ->with('success', "\"{$topic->title}\" was deleted.");
    }

    /**
     * Add one to the view counter, at most once per visitor session,
     * so reloading the page does not inflate the number.
     */
    private function countView(Request $request, ForumTopic $topic): void
    {
        $viewed = $request->session()->get('viewed_topics', []);

        if (in_array($topic->id, $viewed, true)) {
            return;
        }

        // Counting a view is not an edit, so leave updated_at alone.
        ForumTopic::withoutTimestamps(fn () => $topic->increment('views'));

        $request->session()->put('viewed_topics', [...$viewed, $topic->id]);
    }
}
