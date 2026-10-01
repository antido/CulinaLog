<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForumCategoryRequest;
use App\Models\ForumCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ForumCategoryController extends Controller
{
    /**
     * List every forum category, including disabled ones.
     */
    public function index(): Response
    {
        $this->authorize('manage', ForumCategory::class);

        return Inertia::render('Admin/ForumCategories/Index', [
            'categories' => ForumCategory::query()
                ->withCount('topics')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (ForumCategory $category) => [
                    ...$category->summary(),
                    'topics_count' => $category->topics_count,
                ]),
        ]);
    }

    /**
     * Show the form for a new forum category.
     */
    public function create(): Response
    {
        $this->authorize('manage', ForumCategory::class);

        return Inertia::render('Admin/ForumCategories/Create');
    }

    /**
     * Save a new forum category.
     */
    public function store(ForumCategoryRequest $request): RedirectResponse
    {
        $category = new ForumCategory($request->validated());
        $category->slug = ForumCategory::uniqueSlug($category->name);
        $category->save();

        return redirect()
            ->route('admin.forum-categories.index')
            ->with('success', "Forum category \"{$category->name}\" was created.");
    }

    /**
     * Show the form for editing a forum category.
     */
    public function edit(ForumCategory $forumCategory): Response
    {
        $this->authorize('manage', ForumCategory::class);

        return Inertia::render('Admin/ForumCategories/Edit', [
            'category' => $forumCategory->summary(),
        ]);
    }

    /**
     * Save changes to a forum category.
     */
    public function update(ForumCategoryRequest $request, ForumCategory $forumCategory): RedirectResponse
    {
        $forumCategory->update($request->validated());

        return redirect()
            ->route('admin.forum-categories.index')
            ->with('success', "Forum category \"{$forumCategory->name}\" was updated.");
    }

    /**
     * Delete a forum category that has no topics.
     */
    public function destroy(ForumCategory $forumCategory): RedirectResponse
    {
        $this->authorize('manage', ForumCategory::class);

        if ($forumCategory->topics()->exists()) {
            return back()->with(
                'error',
                "\"{$forumCategory->name}\" still has topics, so it cannot be deleted. Disable it to hide it instead.",
            );
        }

        $forumCategory->delete();

        return redirect()
            ->route('admin.forum-categories.index')
            ->with('success', "Forum category \"{$forumCategory->name}\" was deleted.");
    }
}
