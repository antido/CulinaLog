# Deployment

This page describes putting CulinaLog on a live server.

## What the server needs

- PHP 8.3 or newer with `gd` (WebP, JPEG and PNG support), `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`
- Composer 2
- Node.js 20 or newer (only to build the front end; it can be built elsewhere and uploaded)
- MySQL 8 or MariaDB 10.6+
- A web server (Nginx or Apache) with HTTPS
- A way to run a background process (for the queue worker) and a scheduled task

## 1. Settings

Create `.env` on the server from `.env.example` and set:

```dotenv
APP_NAME=CulinaLog
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

LOG_LEVEL=warning

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=culinalog
DB_USERNAME=culinalog
DB_PASSWORD=a-strong-password

SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.example
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="hello@your-domain.example"
```

Then generate the application key once:

```bash
php artisan key:generate
```

Use a database account that only has access to this one database.

> `APP_DEBUG` must be `false`. With it on, error pages show code and settings to visitors.

## 2. Install and build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## 3. Database

```bash
php artisan migrate --force
php artisan db:seed --force
```

In production, `db:seed` creates the roles, permissions, recipe categories and forum categories. It does **not** create the development accounts.

## 4. The first admin

Register an account through the site, verify its email, then give it the admin role once from the server:

```bash
php artisan tinker --execute 'App\Models\User::firstWhere("email", "you@your-domain.example")->syncRoles(["admin"]);'
```

After that, manage roles from **Admin > Users**.

## 5. Uploads

```bash
php artisan storage:link
```

The web server user must be able to write to `storage` and `bootstrap/cache`:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

Include `storage/app/public` in your backups. It holds every uploaded image.

## 6. Speed up

```bash
php artisan optimize
```

This caches the configuration, routes and views. Run it again after every deployment.

## 7. Web server

Point the document root at the **`public`** folder, never the project root. Otherwise `.env` and the source code could be downloaded.

Nginx example:

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.example;
    root /var/www/culinalog/public;
    index index.php;

    client_max_body_size 6M;   # uploads may be up to 5 MB

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}

server {
    listen 80;
    server_name your-domain.example;
    return 301 https://$host$request_uri;
}
```

In `php.ini`, allow 5 MB uploads:

```ini
upload_max_filesize = 6M
post_max_size = 8M
```

### Behind a proxy or load balancer

If HTTPS ends at a proxy (Cloudflare, a load balancer), Laravel must be told to trust it so that it knows requests are secure and sees the visitor's real address for rate limiting. Add this inside `withMiddleware` in `bootstrap/app.php`, using your proxy's address:

```php
$middleware->trustProxies(at: '10.0.0.1');
```

## 8. Background work

The queue and scheduler are not required for the features as built (notifications are saved immediately), but set them up so that anything queued later, such as emailed notifications, is processed.

Queue worker, kept running by Supervisor or systemd:

```bash
php artisan queue:work --tries=3
```

Scheduler, as a cron entry:

```cron
* * * * * cd /var/www/culinalog && php artisan schedule:run >> /dev/null 2>&1
```

## Updating the live site

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force   # picks up new permissions
php artisan optimize
php artisan queue:restart
php artisan up
```

## After deploying, check

- [ ] The home page loads over HTTPS and `http://` redirects to it.
- [ ] Registering sends a real verification email.
- [ ] Uploading a recipe photo works and the photo appears.
- [ ] `https://your-domain.example/no-such-page` shows the friendly "Page not found" page, with no technical details.
- [ ] `/admin` refuses a normal account.
- [ ] `admin@culinalog.test` cannot log in.
- [ ] The response headers include `Content-Security-Policy` and `Strict-Transport-Security`.

The security side of this list is explained in [security.md](security.md).

## Laravel Cloud

The app can also be deployed on [Laravel Cloud](https://cloud.laravel.com/), which provides the server, database, HTTPS, queue worker and scheduler. Set the same environment values there, add a MySQL database, and use `npm ci && npm run build` and `php artisan migrate --force` as the build and deploy commands.

One thing needs changing first: uploaded images are saved on the server's own disk (`storage/app/public`) and served from `/storage`. Platforms like Laravel Cloud do not keep files on the server's disk between deployments, so `App\Services\ImageService` would have to be changed to save to an object-storage bucket and return that bucket's addresses, and the bucket's address added to `img-src` in `App\Http\Middleware\SecurityHeaders`. This has not been done.
