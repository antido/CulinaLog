# Database

CulinaLog uses MySQL. The tables are defined in `database/migrations`. The automated tests run the same migrations on SQLite.

## Tables at a glance

```text
users ─┬─< recipes >── categories
       │     ├─< ingredients
       │     ├─< instructions
       │     ├─< comments (replies point at a parent comment)
       │     └─< reactions
       ├─< forum_topics >── forum_categories
       │     └─< forum_replies (nested through a parent reply)
       ├─< reports ──> recipe | comment | forum_topic | forum_reply
       ├─< moderation_logs
       └─< notifications

roles, permissions and their link tables come from Spatie Laravel Permission.
```

`A ─< B` means "one A has many B".

## Application tables

### users

| Column | Notes |
| --- | --- |
| `name`, `email`, `password` | `email` is unique; `password` is a bcrypt hash |
| `username` | Unique, lowercase letters, numbers and underscores; used in profile addresses |
| `bio` | Up to 500 characters, optional |
| `avatar_path` | Path of the profile image on the public disk, optional |
| `email_verified_at` | Empty until the email address is confirmed |

### categories

`name`, unique `slug`, `description`, `image`, `is_active`. A category that still has recipes cannot be deleted; disable it instead.

### recipes

| Column | Notes |
| --- | --- |
| `user_id` | The author. Deleting a user deletes their recipes |
| `category_id` | The database refuses to delete a category that is in use |
| `title`, `slug`, `description`, `image` | `slug` is unique and never changes |
| `prep_time`, `cook_time` | Minutes |
| `servings`, `difficulty` | `difficulty` is `easy`, `medium` or `hard` |
| `status` | `draft`, `published` or `archived` |
| `is_hidden` | Set by moderators. A hidden recipe is not public whatever its status |
| `published_at` | Set the first time the recipe is published |

A recipe is public only when `status = published` and `is_hidden = false`. The index on (`status`, `is_hidden`, `published_at`) serves the public recipe list.

### ingredients and instructions

- `ingredients`: `recipe_id`, `name`, `quantity` (optional decimal), `unit` (optional), `sort_order`.
- `instructions`: `recipe_id`, `step_number`, `instruction`.

Both are deleted with their recipe. They are saved together with the recipe in one database transaction.

### comments

| Column | Notes |
| --- | --- |
| `recipe_id` | The recipe commented on |
| `user_id` | Empty for a guest |
| `parent_id`, `depth` | Set for replies. `depth` is 0, 1 or 2 |
| `guest_name` | Optional display name typed by a guest |
| `guest_token_hash` | SHA-256 hash of the guest's cookie token |
| `body` | The text |
| `status` | `visible`, `pending` (awaiting approval) or `hidden` |

### reactions

`recipe_id`, `user_id` (or `guest_token_hash` for a guest), `type` (`like`, `love`, `wow`). Two unique indexes make sure a person has at most one reaction per recipe.

### forum_categories, forum_topics, forum_replies

- `forum_categories`: `name`, unique `slug`, `description`, `is_active`, `sort_order`.
- `forum_topics`: `user_id`, `category_id`, `title`, unique `slug`, `body`, `status` (`visible` or `hidden`), `is_pinned`, `is_locked`, `views`, `last_activity_at`.
- `forum_replies`: `topic_id`, `user_id` (empty for a guest), `parent_id`, `depth`, `guest_token_hash`, `body`, `status` (`visible`, `pending`, `hidden`).

Topics are listed pinned first, then by `last_activity_at`.

### reports

| Column | Notes |
| --- | --- |
| `user_id` | The reporter; empty for a guest or if the member has left |
| `reportable_type`, `reportable_id` | What was reported. The type is a short name: `recipe`, `comment`, `forum_topic` or `forum_reply` |
| `reason` | `spam`, `harassment`, `inappropriate`, `copyright`, `misleading` or `other` |
| `description` | Optional note from the reporter |
| `status` | `pending`, `resolved` or `dismissed` |
| `reviewed_by`, `reviewed_at` | The moderator who reviewed it, and when |

### moderation_logs

`user_id` (the moderator), `action` (for example `hid`, `restored`, `pinned`), the subject's type and id, and `subject_label`, a short copy of the title or text so the entry still makes sense after the content is deleted.

### notifications

Laravel's standard table for in-app notifications. `data` holds the message and the address to open.

## Framework tables

`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens` and `migrations` are created by Laravel. Sessions, cache and the queue are stored in the database by default.

## Roles and permissions tables

`roles`, `permissions`, `model_has_roles`, `model_has_permissions` and `role_has_permissions` belong to Spatie Laravel Permission. See [authorization.md](authorization.md).

## What is deleted together

| When this is deleted | These go with it |
| --- | --- |
| A user | Their recipes (and everything under them), comments, reactions, forum topics, forum replies and moderation log entries. Their reports stay, without a reporter. |
| A recipe | Its ingredients, instructions, comments, reactions and image file |
| A comment or forum reply | The replies under it |
| A forum topic | Its replies |

## Seeders

| Seeder | Creates | Runs in production |
| --- | --- | --- |
| `RolesAndPermissionsSeeder` | The 19 permissions and three roles | Yes |
| `CategorySeeder` | Nine recipe categories | Yes |
| `ForumCategorySeeder` | Five forum categories | Yes |
| `DevelopmentUserSeeder` | Three sample accounts | No |
| `DevelopmentContentSeeder` | Sample recipes and forum topics | No |

All seeders can be run more than once without creating duplicates.
