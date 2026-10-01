# Installation

This guide sets CulinaLog up on your own computer for development.

## 1. Install the tools

| Tool | Version | Check with |
| --- | --- | --- |
| PHP | 8.3 or newer | `php -v` |
| Composer | 2 | `composer -V` |
| Node.js | 20 or newer | `node -v` |
| MySQL | 8 (or MariaDB 10.6+) | `mysql --version` |

PHP needs these extensions: `gd` (with WebP), `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`. On XAMPP, open `php.ini` and make sure these lines have no `;` in front:

```ini
extension=gd
extension=pdo_mysql
extension=fileinfo
```

## 2. Get the code and its packages

```bash
cd culinalog
composer install
npm install
```

`npm install` does not run package install scripts (`.npmrc` sets `ignore-scripts=true`). That is deliberate and nothing in this project needs them.

## 3. Create the settings file

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell use `Copy-Item .env.example .env`.

Edit `.env`:

```dotenv
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=culinalog
DB_USERNAME=root
DB_PASSWORD=
```

If your MySQL uses another port, change `DB_PORT`.

## 4. Create the database

In MySQL (phpMyAdmin, MySQL Workbench or the command line):

```sql
CREATE DATABASE culinalog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then create the tables and the starting data:

```bash
php artisan migrate --seed
```

## 5. Link the upload folder

```bash
php artisan storage:link
```

Uploaded images are saved in `storage/app/public`. This command makes them visible to the browser at `/storage`.

## 6. Start the app

The simplest way is one command that starts the PHP server, the queue worker, the log viewer and Vite together:

```bash
composer dev
```

Or run the two essential parts yourself in two terminals:

```bash
php artisan serve
npm run dev
```

Open <http://localhost:8000>.

## 7. Log in

The seeder created three accounts, all with the password `password`:

| Role | Email |
| --- | --- |
| Admin | `admin@culinalog.test` |
| Moderator | `moderator@culinalog.test` |
| User | `user@culinalog.test` |

## Registering a new account locally

New accounts must confirm their email address before they can write recipes or forum topics. While developing, no real email is sent (`MAIL_MAILER=log`). To verify a new account:

1. Register.
2. Open `storage/logs/laravel.log` and go to the end.
3. Copy the link that starts with `http://localhost:8000/verify-email/` and open it in the same browser.

## Starting over

```bash
php artisan migrate:fresh --seed
```

This deletes every table and all data, then rebuilds and reseeds them.

## Checking the installation

```bash
php artisan about      # shows the environment, database and drivers in use
php artisan test       # all tests should pass
npm run build          # the front end should build without errors
```
