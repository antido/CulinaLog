# Architecture

CulinaLog is a standard Laravel application. The server decides what data a page gets; React draws the page. Inertia.js connects the two, so there is no separate API.

## How a request flows

Taking "open a recipe" as the example:

1. The browser asks for `/recipes/garlic-fried-rice`.
2. `routes/web.php` matches the address to `RecipeController@show`.
3. Laravel finds the `Recipe` whose `slug` is `garlic-fried-rice`.
4. The controller asks `RecipePolicy` whether this visitor may see it. If not, the answer is "404 not found".
5. The controller builds a plain array of exactly the fields the page needs and calls `Inertia::render('Recipes/Show', [...])`.
6. Inertia sends that data to the React component `resources/js/Pages/Recipes/Show.jsx`, which draws the page.

Saving something (for example a comment) adds two steps before the controller runs: a **Form Request** class checks permission and validates the input, and a **throttle** middleware limits how often it can be done.

## Folder guide

```text
app/
  Enums/                 Fixed lists: recipe status, difficulty, report reason, ...
  Http/
    Controllers/         One class per area. Admin/ and Moderation/ hold the staff areas.
    Middleware/          HandleInertiaRequests (shared page data), SecurityHeaders
    Requests/            Form Requests: authorization + validation for each form
  Models/                Eloquent models. Concerns/HasUniqueSlug is shared slug logic.
  Notifications/         The five in-app notifications
  Policies/              Who may do what to each model
  Providers/             AppServiceProvider: rate limits, search helper, safety settings
  Services/              Small helper classes (see below)
bootstrap/app.php        Middleware registration and error-page handling
config/community.php     Community settings: guest features, upload size, reply depth
database/
  factories/             Fake data for tests
  migrations/            The table definitions
  seeders/               Roles, permissions, categories and development sample data
resources/
  css/app.css            Tailwind and the design tokens (colours, fonts)
  js/
    Components/          Reusable React components (Button, Card, Pagination, ...)
    Layouts/             AppLayout (main frame) and GuestLayout (sign-in pages)
    Pages/               One file per screen, matching Inertia::render() names
    utils.js             Small shared helpers and hooks
  views/app.blade.php    The single HTML shell, including SEO tags
routes/
  web.php                Public and member routes
  auth.php               Login, registration, password and verification routes
  forum.php              Forum routes
  admin.php              Admin area (permission protected)
  moderation.php         Moderation area (permission protected)
tests/Feature/           The automated tests, one file per area
```

## Services

Each service is a small class with one job.

| Class | Job |
| --- | --- |
| `ImageService` | Validates, resizes and re-saves uploaded images as WebP |
| `GuestIdentity` | Gives guests an anonymous cookie token and returns its hash |
| `ModerationService` | Hides and restores content and writes the moderation log |
| `ActivityNotifier` | Decides who is notified about a comment, reaction or reply |
| `SeoService` | Builds page titles, descriptions and recipe structured data |

## Conventions used throughout

- **Controllers stay thin.** They authorize, ask models or services for data, and return a page or a redirect.
- **Validation lives in Form Requests** (`app/Http/Requests`), not in controllers.
- **Authorization lives in Policies** (`app/Policies`) and permission middleware on routes.
- **Models never send themselves to the browser whole.** Each has a method such as `toCard()`, `summary()` or `toThread()` that returns only safe fields.
- **Fields the user must not control are not fillable.** Owner, slug, status and similar columns are assigned explicitly in controller code.
- **Lists are paginated** and load their related records up front (`->with([...])`) to avoid one query per row. In development and tests, Laravel throws an error if a query-per-row slips in.
- **Slugs stay the same after editing**, so links never break.

## Front end

- `resources/js/Pages` mirrors the names passed to `Inertia::render()`.
- `resources/js/Components` holds anything used on more than one page. The `Discussion/` folder is the comment thread used by both recipes and the forum.
- Data shared with every page (signed-in user, permission names, unread notification count, flash messages) is set in `app/Http/Middleware/HandleInertiaRequests.php`.
- `usePermissions()` in `utils.js` hides links a user cannot use. It is a convenience only; the server checks every request.
- Styling uses Tailwind utility classes. The colours and fonts are defined once in `resources/css/app.css` under `@theme`, giving classes such as `bg-rice`, `text-ink` and `border-line`.

## Adding a feature: the usual steps

1. Migration for any new table or column.
2. Model, with a factory.
3. Policy, if the feature needs "who may do this" rules.
4. Form Request for each form.
5. Controller and routes.
6. React page in `resources/js/Pages`.
7. Feature test in `tests/Feature`, including a test that an unauthorized user is refused.
