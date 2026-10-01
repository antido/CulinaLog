<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * List every category, including disabled ones.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Category::class);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()
                ->orderBy('name')
                ->paginate(20)
                ->through(fn (Category $category) => $category->summary()),
        ]);
    }

    /**
     * Show the form for a new category.
     */
    public function create(): Response
    {
        $this->authorize('create', Category::class);

        return Inertia::render('Admin/Categories/Create');
    }

    /**
     * Save a new category.
     */
    public function store(StoreCategoryRequest $request, ImageService $images): RedirectResponse
    {
        $category = new Category($request->safe()->only(['name', 'description', 'is_active']));
        $category->slug = Category::uniqueSlug($category->name);

        if ($request->hasFile('image')) {
            $category->image = $images->store($request->file('image'), 'categories', 1200);
        }

        $category->save();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" was created.");
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category): Response
    {
        $this->authorize('update', $category);

        return Inertia::render('Admin/Categories/Edit', [
            'category' => $category->summary(),
        ]);
    }

    /**
     * Save changes to a category. The slug stays the same so links keep working.
     */
    public function update(UpdateCategoryRequest $request, Category $category, ImageService $images): RedirectResponse
    {
        $category->fill($request->safe()->only(['name', 'description', 'is_active']));

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $images->delete($category->image);
            $category->image = null;
        }

        if ($request->hasFile('image')) {
            $category->image = $images->store($request->file('image'), 'categories', 1200);
        }

        $category->save();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" was updated.");
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->recipes()->exists()) {
            return back()->with(
                'error',
                "\"{$category->name}\" still has recipes, so it cannot be deleted. Disable it to hide it instead.",
            );
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category \"{$category->name}\" was deleted.");
    }
}
