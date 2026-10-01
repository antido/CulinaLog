# Testing

CulinaLog has 274 automated tests in `tests/Feature`. They send real requests through the application and check the response and the database.

## Running the tests

```bash
php artisan test                         # everything
php artisan test --filter=RecipeTest     # one file
php artisan test --filter=test_user_cannot_edit_or_delete_another_users_recipe
```

`composer test` clears the cached configuration first, then runs everything.

## How the test environment works

`phpunit.xml` sets up a separate environment:

- **Database:** SQLite in memory. Your MySQL data is never touched and nothing needs to be created.
- **Fresh start:** each test class uses `RefreshDatabase`, so every test begins with empty tables.
- **Roles:** `tests/TestCase.php` seeds the roles and permissions before the tests run.
- **No build needed:** `withoutVite()` means tests pass without running `npm run build`.
- **Mail, cache and sessions** are kept in memory.

## What is covered

| File | Covers |
| --- | --- |
| `Auth/*` | Login, registration, email verification, password reset and confirmation |
| `RolesAndPermissionsTest` | Seeded roles, role on registration, admin and moderator route access, role changes |
| `ProfileTest` | Profile editing, photo upload, public profile fields |
| `CategoryTest` | Public and admin category pages, slugs, images |
| `RecipeTest` | Recipe create, edit, publish, archive, delete, visibility, ownership |
| `RecipePartsTest` | Ingredients and instructions: order, validation, transaction rollback |
| `CommentTest` | Comments, replies, guests, duplicates, rate limit, spam trap, HTML escaping, deletion |
| `ReactionTest` | Member and guest reactions, toggling, one-per-person, rate limit |
| `ForumTest` | Topics, replies, guests, pin, lock, hide, search, forum categories |
| `ReportAndModerationTest` | Reporting, reviewing, hiding, the approval queue, the moderation log |
| `NotificationTest` | Who is notified, who is not, reading notifications |
| `DashboardTest` | Admin and moderator dashboards, roles and permissions screens |
| `SearchAndSeoTest` | Search fields, filters, sorting, query counts, page tags and structured data |
| `SecurityTest` | Headers, sessions, login lockout, error pages, uploads, data leaks, mass assignment |

## Negative tests

Many tests check that something is **refused**. Examples:

- a user editing another user's recipe by changing the address gets 403;
- sending `user_id`, `role`, `status` or `is_hidden` in a form changes nothing;
- a guest opening a draft recipe gets 404;
- a script renamed to `photo.jpg` is rejected and nothing is stored;
- a normal user calling any moderation address gets 403 and nothing changes;
- the fourth guest comment within a minute gets 429.

When you add a protected feature, add a test like these beside the test that shows it working.

## Writing a test

Create the file:

```bash
php artisan make:test --phpunit RecipeRatingTest
```

A typical test creates data with factories, acts as a user, sends a request and checks the result:

```php
public function test_user_cannot_edit_another_users_recipe(): void
{
    $recipe = Recipe::factory()->create(['title' => 'Original']);
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->put("/recipes/{$recipe->slug}", ['title' => 'Hijacked'])
        ->assertForbidden();

    $this->assertSame('Original', $recipe->fresh()->title);
}
```

### Useful factory states

| Factory | States |
| --- | --- |
| `User::factory()` | `->admin()`, `->moderator()`, `->unverified()` |
| `Recipe::factory()` | `->draft()`, `->archived()`, `->hidden()` |
| `Comment::factory()` | `->guest('Name')`, `->pending()`, `->hidden()`, `->replyTo($comment)` |
| `ForumTopic::factory()` | `->hidden()`, `->pinned()`, `->locked()` |
| `ForumReply::factory()` | `->guest()`, `->pending()`, `->hidden()`, `->replyTo($reply)` |
| `Report::factory()` | `->about($content)`, `->resolved()` |
| `Category::factory()`, `ForumCategory::factory()` | `->inactive()` |

### Checking what a page receives

```php
$this->get('/recipes')
    ->assertInertia(fn ($page) => $page
        ->component('Recipes/Index')
        ->has('recipes.data', 1)
        ->where('recipes.data.0.title', 'Visible Dish')
        ->missing('recipes.data.0.author.email')
    );
```

### Guests in tests

To act as a particular guest, send the cookie with a 64-character token:

```php
$this->withCookie('guest_token', str_repeat('a', 64))
    ->post("/recipes/{$recipe->slug}/reactions", ['type' => 'like']);
```

A cookie set with `withCookie()` stays for the rest of that test.

## Before you finish a change

```bash
vendor/bin/pint        # format the PHP code
php artisan test       # all tests pass
npm run build          # the front end builds
```

## Full verification

After larger changes, also check that a clean database can be built:

```bash
php artisan route:list
php artisan migrate:fresh --seed
```

`migrate:fresh` deletes all data in the development database.

## Checks done by hand

These were run in a real browser while building the app and are worth repeating after interface changes:

- every page at 320, 375, 425, 768, 1024, 1280 and 1440 pixels wide, with no sideways scrolling;
- the axe accessibility checker (WCAG 2.1 A and AA) on the main pages;
- keyboard only: Tab reaches everything, the first Tab stop is "Skip to content", Escape closes dialogs.
