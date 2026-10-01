# Recipes

This page covers recipes, categories, comments and reactions.

## Pages

| Address | Who | What |
| --- | --- | --- |
| `/` | Everyone | Latest six recipes and the categories |
| `/recipes` | Everyone | All public recipes, newest first, 12 a page |
| `/recipes/{slug}` | Everyone (if public) | One recipe |
| `/recipes/create` | Verified members | New recipe form |
| `/recipes/{slug}/edit` | The author, or an admin | Edit form |
| `/dashboard` | Members | "My recipes", in every status |
| `/categories`, `/categories/{slug}` | Everyone | Category list and one category's recipes |
| `/search` | Everyone | Search with filters |
| `/admin/recipes` | Admins | Every recipe, with search and status filter |
| `/admin/categories` | Admins | Manage categories |

## Recipe status

| Status | Who can see it |
| --- | --- |
| `draft` | Only the author (and staff) |
| `published` | Everyone |
| `archived` | Only the author (and staff) |

Separately, a moderator can **hide** a recipe. A hidden recipe is not public whatever its status, and its author cannot un-hide it.

A recipe is public when it is published **and** not hidden. `Recipe::visible()` is the query scope for that, and `isVisible()` checks a single recipe.

Authors change status in the form, or with the **Publish** and **Archive** buttons on "My recipes". `published_at` is set the first time a recipe is published.

## Saving a recipe

`RecipeController@store` and `@update`:

1. `StoreRecipeRequest` / `UpdateRecipeRequest` check permission and validate every field.
2. The owner is set to the signed-in user. It is never read from the form.
3. A slug is made from the title (`garlic-fried-rice`, then `garlic-fried-rice-2` if taken). It never changes afterwards.
4. The photo, if any, goes through `ImageService`.
5. The recipe, its ingredients and its instructions are saved in **one database transaction**. If any part fails, nothing is saved and the new photo is removed.

### Field rules

| Field | Rule |
| --- | --- |
| Title | Required, up to 150 characters |
| Category | Required, must be an active category |
| Description | Required, up to 5,000 characters |
| Photo | Optional; JPEG, PNG or WebP, up to 5 MB |
| Preparation and cooking time | Whole minutes, 0 to 1,440 |
| Servings | 1 to 100 |
| Difficulty | `easy`, `medium`, `hard` |
| Ingredients | 1 to 50 rows. Name required (150 characters); quantity optional, a number; unit optional (30 characters) |
| Instructions | 1 to 50 steps, each up to 2,000 characters |

## Ingredients and instructions

The form lets the author add, remove and reorder rows with **Up**, **Down** and **Remove** buttons. The order of the rows in the form is the order saved: the server numbers them (`sort_order`, `step_number`) as they arrive.

On each save the lists are replaced in full. On the recipe page, ingredients appear as a checklist a cook can tick off (the ticks are not saved), and instructions as numbered steps.

## Categories

Admins create, edit, disable and delete categories at `/admin/categories`.

- A disabled category is hidden from visitors and cannot be chosen for a new recipe. Existing recipes keep it.
- A category that has recipes cannot be deleted. Disable it instead.

## Comments

Anyone can comment on a public recipe, including guests (switch off with `guest_comments` in `config/community.php`).

- Replies nest up to three levels. A reply to a third-level comment is placed beside it.
- Members appear under their account name. Guests appear under the optional name they type, or as "Anonymous".
- A guest comment that contains a link is held as `pending` until a moderator approves it.
- A member can delete their own comment. Moderators can delete or hide any comment.
- The recipe's author cannot delete other people's comments; they can report them.

Top-level comments are paginated ten a page, newest first, each with its replies.

## Reactions

Each person has at most one reaction per recipe: **like**, **love** or **wow**.

- Pressing the same reaction again removes it.
- Pressing a different one replaces it.
- Guests may react too (`guest_reactions` in `config/community.php`).

The database enforces "one per person per recipe" with unique indexes. The reaction count is also what "Most reactions" sorts by in search.

## Search

`/search` looks in recipe titles, descriptions, ingredient names, category names and author names. Filters: category, difficulty, "ready in" (15, 30, 60 or 120 minutes of preparation plus cooking), and sort by newest, oldest or most reactions. It can also search forum topics.

Only public recipes are returned. Filter values that are not on the allowed list are ignored.

## Search engines

Each recipe page is sent with its own title, description, canonical address and Open Graph tags, plus schema.org **Recipe** structured data (times, servings, ingredients, steps). `App\Services\SeoService` builds these and `resources/views/app.blade.php` prints them. Drafts, archived and hidden recipes are marked `noindex`.

## Settings

`config/community.php`:

| Setting | Default | Meaning |
| --- | --- | --- |
| `max_image_kilobytes` | 5120 | Largest upload, in KB |
| `guest_comments` | `true` | Guests may comment on recipes |
| `guest_reactions` | `true` | Guests may react |
| `comment_depth` | 3 | Levels of nested replies |
| `duplicate_window_minutes` | 10 | How long the same text is refused as a duplicate |
