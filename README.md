# CulinaLog

CulinaLog is a recipe community web app. Members write recipes with ingredients and step-by-step instructions, comment and react, and talk in a forum. Guests can comment, react and reply without an account. Moderators and admins keep things tidy.

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Environment setup](#environment-setup)
- [Database setup, migration and seeding](#database-setup-migration-and-seeding)
- [Development commands](#development-commands)
- [Testing](#testing)
- [Production build and deployment](#production-build-and-deployment)
- [Security notes](#security-notes)
- [Troubleshooting](#troubleshooting)
- [More documentation](#more-documentation)

## What is in it

| Area | What it does |
| --- | --- |
| Accounts | Register, log in, email verification, password reset, profile with photo and bio |
| Roles | `admin`, `moderator`, `user`, built on 19 permissions (Spatie Laravel Permission) |
| Recipes | Create, edit, publish, archive, delete; photo; ordered ingredients and steps; categories |
| Community | Threaded comments, like / love / wow reactions, guest participation |
| Forum | Categories, topics, nested replies, pinned and locked topics, search |
| Moderation | Reports, hide and restore, approval queue for guest posts, activity log |
| Notifications | In-app notices for comments, replies, reactions, forum replies and reviewed reports |
| Search | Recipes by title, description, ingredient, category or author, with filters; forum topics |
| Dashboards | Separate admin and moderator dashboards |

**Stack:** Laravel 13, PHP 8.3+, MySQL, Laravel Breeze, Inertia.js 2, React 18, Tailwind CSS 4, Vite 8, Spatie Laravel Permission 8.

## Requirements

- PHP 8.3 or newer with the `gd` extension (with WebP support), `pdo_mysql` and `mbstring`
- Composer 2
- Node.js 20 or newer and npm
- MySQL 8 (or MariaDB 10.6+)

Check PHP's image support with:

```bash
php -r "print_r(gd_info());"
```

`WebP Support`, `JPEG Support` and `PNG Support` must all be `true`.

## Installation

```bash
git clone <your-repository-url> culinalog
cd culinalog

composer install
npm install

cp .env.example .env        # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan storage:link
```

`storage:link` makes uploaded images reachable at `/storage/...`. Without it, photos do not appear.

## Environment setup

Open `.env` and set at least these:

| Setting | Meaning |
| --- | --- |
| `APP_URL` | The address you open in the browser, for example `http://localhost:8000` |
| `DB_HOST`, `DB_PORT` | Where MySQL runs. The default port is `3306` |
| `DB_DATABASE` | The database name, for example `culinalog` |
| `DB_USERNAME`, `DB_PASSWORD` | Your MySQL login |
| `MAIL_MAILER` | `log` while developing (see below); `smtp` on a live site |

With `MAIL_MAILER=log`, no email is sent. Each email is written to `storage/logs/laravel.log` instead. **That is where you find the verification link after registering a new account locally.**

Never commit `.env`. It is listed in `.gitignore`.

## Database setup, migration and seeding

1. Create an empty database:

   ```sql
   CREATE DATABASE culinalog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Create the tables and the starting data:

   ```bash
   php artisan migrate --seed
   ```

Seeding always creates the roles, permissions, recipe categories and forum categories. Outside production it also creates three sample accounts and a few sample recipes and forum topics:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@culinalog.test` | `password` |
| Moderator | `moderator@culinalog.test` | `password` |
| User | `user@culinalog.test` | `password` |

These accounts are skipped when `APP_ENV=production`.

To wipe the database and start again (this deletes all data):

```bash
php artisan migrate:fresh --seed
```

## Development commands

| Task | Command |
| --- | --- |
| Start everything (server, queue, logs, Vite) | `composer dev` |
| Start only the PHP server | `php artisan serve` |
| Start only Vite, with hot reload | `npm run dev` |
| Build the front end once | `npm run build` |
| List all routes | `php artisan route:list --except-vendor` |
| Format PHP code | `vendor/bin/pint` |

Then open `http://localhost:8000`.

If you run `php artisan serve` without `npm run dev`, run `npm run build` first and again after each front-end change.

## Testing

```bash
php artisan test
```

The tests use an in-memory SQLite database, so they never touch your MySQL data and need no setup. There are 274 tests. See [docs/testing.md](docs/testing.md).

## Production build and deployment

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan storage:link
php artisan optimize
```

Set `APP_ENV=production` and `APP_DEBUG=false`, serve the site over HTTPS, and point the web server at the `public` folder. The full checklist is in [docs/deployment.md](docs/deployment.md).

## Security notes

- Every protected action is checked on the server with a permission or a policy. Hiding a button is never the only protection.
- The owner, role, status and moderation fields of a record are set by server code and are not read from forms.
- Uploaded images are decoded, resized and re-saved as WebP under a random name. The original file is never stored.
- Guests are recognised by a random token in an encrypted, HttpOnly cookie. Only a SHA-256 hash of it is stored. No IP address is saved.
- User-written text is always shown as plain text, and pages send a Content Security Policy.
- Posting is rate limited, with stricter limits for guests.

Details are in [docs/security.md](docs/security.md).

## Troubleshooting

| Problem | Cause and fix |
| --- | --- |
| `Unable to locate file in Vite manifest` | The front end has not been built. Run `npm run build`, or keep `npm run dev` running. |
| Uploaded photos do not show | Run `php artisan storage:link`. |
| `SQLSTATE[HY000] [2002]` connection refused | MySQL is not running, or `DB_HOST` / `DB_PORT` in `.env` are wrong. |
| `There is no role named 'user'` | The roles were not seeded. Run `php artisan db:seed --class=RolesAndPermissionsSeeder`. |
| "This file could not be read as an image" | PHP's `gd` extension lacks WebP, JPEG or PNG support. Enable `extension=gd` in `php.ini`. |
| Cannot post after registering | The email address is not verified yet. Open the link in `storage/logs/laravel.log`. |
| A role's new permissions do not take effect | Run `php artisan permission:cache-reset`. |
| "The page expired" after a long pause | The form's security token timed out. Reload the page and try again. |
| "You are doing that too often" | A rate limit was reached. Wait a minute. |
| A page looks unstyled on the live site | Run `npm run build` on the server, and check that `APP_URL` matches the real address. |

## More documentation

| File | Contents |
| --- | --- |
| [docs/installation.md](docs/installation.md) | Step-by-step setup, including Windows and XAMPP |
| [docs/architecture.md](docs/architecture.md) | How the code is organised and how a request flows |
| [docs/database.md](docs/database.md) | Every table and how they relate |
| [docs/authentication.md](docs/authentication.md) | Registration, login, verification, password reset |
| [docs/authorization.md](docs/authorization.md) | Roles, permissions and policies |
| [docs/security.md](docs/security.md) | The protections in place and where to find them |
| [docs/recipes.md](docs/recipes.md) | Recipes, categories, comments and reactions |
| [docs/forum.md](docs/forum.md) | Forum, reports, moderation and notifications |
| [docs/testing.md](docs/testing.md) | Running and writing tests |
| [docs/deployment.md](docs/deployment.md) | Going live |

The numbered files in `docs/` (`01-...` to `14-...`) and `docs/run-task.md` are the task briefs the application was built from.
