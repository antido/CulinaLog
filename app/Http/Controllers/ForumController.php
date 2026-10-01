<?php

namespace App\Http\Controllers;

use App\Models\ForumCategory;
use App\Models\ForumTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ForumController extends Controller
{
    /**
     * The forum home: the categories and the active topics.
     * With ?q=words it shows the topics that match instead.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q'));

        $topics = ForumTopic::query()
            ->visible()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->when($search !== '', function ($query) use ($search): void {
                // whereContains is defined in AppServiceProvider. It binds the
                // text as a parameter and treats % and _ as ordinary characters.
                $query->where(function ($query) use ($search): void {
                    $query->whereContains('title', $search)
                        ->orWhereContains('body', $search);
                });
            })
            ->with(['user', 'category'])
            ->withCount('visibleReplies')
            ->listingOrder()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ForumTopic $topic) => $topic->toCard());

        return Inertia::render('Forum/Index', [
            'categories' => ForumCategory::query()
                ->active()
                ->withCount(['topics' => fn ($query) => $query->visible()])
                ->get()
                ->map(fn (ForumCategory $category) => [
                    ...$category->summary(),
                    'topics_count' => $category->topics_count,
                ]),
            'topics' => $topics,
            'filters' => ['q' => $search],
        ])->withViewData('meta', [
            'title' => 'Forum',
            'description' => 'Ask cooking questions and compare notes with other home cooks in the CulinaLog forum.',
            // Forum search results are not worth indexing; the plain forum page is.
            'noindex' => $search !== '',
        ]);
    }

    /**
     * The topics in one forum category, pinned topics first.
     */
    public function category(ForumCategory $forumCategory): Response
    {
        abort_unless(Gate::allows('view', $forumCategory), 404);

        return Inertia::render('Forum/Category', [
            'category' => $forumCategory->summary(),
            'topics' => $forumCategory->topics()
                ->visible()
                ->with(['user', 'category'])
                ->withCount('visibleReplies')
                ->listingOrder()
                ->paginate(15)
                ->through(fn (ForumTopic $topic) => $topic->toCard()),
        ]);
    }
}
