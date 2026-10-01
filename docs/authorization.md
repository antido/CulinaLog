# Authorization

Authorization answers "what are you allowed to do?". CulinaLog uses two tools together:

- **Permissions and roles** from Spatie Laravel Permission, for what a kind of user may do in general.
- **Policies**, for rules about one particular record, such as "only the author may edit this recipe".

> **The most important rule:** hiding a button in the interface is not protection. Every protected action is checked on the server.

## Roles

| Role | Who | In short |
| --- | --- | --- |
| `user` | Every registered member | Write their own recipes, comment, react, post in the forum, report |
| `moderator` | Trusted members | Everything a user can do, plus hide and restore content, approve guest posts and review reports |
| `admin` | Site owners | Everything, including users, roles, categories and editing or deleting any content |

Each user has exactly one role. An admin changes it on **Admin > Users**. Nobody can change their own role.

## Permissions

Permissions are defined in `database/seeders/RolesAndPermissionsSeeder.php`.

| Permission | Allows | user | moderator | admin |
| --- | --- | :-: | :-: | :-: |
| `recipes.create` | Publish and manage their own recipes | ✓ | ✓ | ✓ |
| `comments.create` | Comment on recipes | ✓ | ✓ | ✓ |
| `reactions.create` | React to recipes | ✓ | ✓ | ✓ |
| `forum.create` | Start topics and reply | ✓ | ✓ | ✓ |
| `reports.create` | Report content | ✓ | ✓ | ✓ |
| `recipes.moderate` | Hide and restore any recipe | | ✓ | ✓ |
| `comments.moderate` | Approve, hide and restore any comment | | ✓ | ✓ |
| `comments.delete` | Delete any comment | | ✓ | ✓ |
| `forum.moderate` | Pin, lock, hide and restore forum content | | ✓ | ✓ |
| `reports.review` | Review and resolve reports | | ✓ | ✓ |
| `dashboard.moderator` | Open the moderator dashboard | | ✓ | ✓ |
| `recipes.manage` | Edit and delete any recipe | | | ✓ |
| `forum.manage` | Manage forum categories, delete forum content | | | ✓ |
| `categories.manage` | Manage recipe categories | | | ✓ |
| `users.view` | See the user list | | | ✓ |
| `users.manage` | Change roles, delete users | | | ✓ |
| `roles.manage` | Create roles, choose their permissions | | | ✓ |
| `permissions.view` | See the permission list | | | ✓ |
| `dashboard.admin` | Open the admin dashboard | | | ✓ |

Admins hold every permission explicitly. There is no hidden "admins can do anything" shortcut, so permission checks work the same way for every role.

Access always follows the permission, not the role name. If an admin gives a custom role `reports.review`, people with that role can review reports.

## The three layers of checking

A request to change something passes up to three checks.

**1. Route middleware** (`routes/admin.php`, `routes/moderation.php`):

```php
Route::get('users', [UserController::class, 'index'])
    ->middleware('permission:users.view');
```

**2. Form Request `authorize()`**, which usually asks a policy:

```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('recipe'));
}
```

**3. Policy** (`app/Policies`), with the rule itself:

```php
public function update(User $user, Recipe $recipe): bool
{
    if ($user->can('recipes.manage')) {
        return true;
    }

    return $user->id === $recipe->user_id && $user->can('recipes.create');
}
```

Controllers without a Form Request call `$this->authorize('delete', $recipe)` directly.

## Policies

| Policy | Rules it holds |
| --- | --- |
| `RecipePolicy` | Who can see drafts and hidden recipes; only the author or a manager edits or deletes; moderators hide and restore |
| `CommentPolicy` | A member deletes their own comment; staff delete or moderate any |
| `CategoryPolicy` | Disabled categories are visible to managers only |
| `ForumTopicPolicy` | Viewing hidden topics, editing, deleting, moderating, and who may reply |
| `ForumReplyPolicy` | A member deletes their own reply; managers delete any; moderators moderate |
| `ForumCategoryPolicy` | Managing forum categories |
| `ReportPolicy` | Listing and reviewing reports |
| `UserPolicy` | Viewing users, changing roles, deleting users (never yourself) |

## Things a request can never set

These are assigned by server code and are not fillable, so sending them in a form has no effect:

- the owner of a recipe, comment, topic or reply (`user_id`);
- a user's role;
- a recipe's hidden flag, slug and publish date;
- the status of a comment, topic, reply or report;
- a topic's pinned and locked flags and view count;
- who reviewed a report.

## "Not found" instead of "forbidden"

When someone asks for a draft recipe, a hidden topic or a disabled category they may not see, the answer is **404 not found**, not **403 forbidden**. That way the response does not confirm that the item exists.

## Managing roles

**Admin > Roles** lets an admin tick which permissions each role has and add custom roles.

- The `admin` role always has every permission and cannot be changed, so admins cannot lock themselves out.
- The three built-in roles cannot be deleted.
- A custom role can be deleted only when no user has it.

## In the interface

Every page receives the signed-in user's permission names. Components use them to hide what a user cannot use:

```jsx
const { can } = usePermissions();

{can('forum.moderate') && <Button>Lock</Button>}
```

This only tidies the screen. The server refuses the request either way.

## Adding a permission

1. Add it to `PERMISSIONS` in `RolesAndPermissionsSeeder` and to the role lists that should have it.
2. Run `php artisan db:seed --class=RolesAndPermissionsSeeder`.
3. Check it in a policy or with `->middleware('permission:your.permission')`.
4. Add a test showing that a user without it is refused.
