# Authentication

Authentication answers "who are you?". CulinaLog uses Laravel Breeze, which provides the login, registration, password and email verification screens.

The routes are in `routes/auth.php`, the controllers in `app/Http/Controllers/Auth`, and the React pages in `resources/js/Pages/Auth`.

## Registration

`RegisteredUserController@store` asks for a name, a username, an email address and a password.

- The username must be unique, 3 to 30 characters, lowercase letters, numbers and underscores.
- The password is stored as a bcrypt hash, never as text.
- Every new account receives the `user` role. The role is assigned in the controller and is never read from the form, so nobody can register as an admin.
- At most 6 registration attempts a minute are accepted from one address.

## Email verification

The `User` model implements `MustVerifyEmail`. After registering, a member is sent a link and must open it before they can:

- write or edit recipes,
- start or edit forum topics,
- open the admin or moderation areas.

These routes carry the `verified` middleware. Commenting and reacting do not need verification, because guests may do those too.

While developing, emails are written to `storage/logs/laravel.log` (`MAIL_MAILER=log`). Changing your email address in account settings marks it unverified again.

## Login

`AuthenticatedSessionController@store` uses `LoginRequest`.

- After 5 failed attempts for the same email address and network address, further attempts are refused for a short time.
- A wrong password and an unknown email address produce the same message, so the form does not reveal which accounts exist.
- On success the session ID is regenerated, which prevents session fixation.
- "Remember me" keeps the member signed in with a long-lived cookie.

## Logout

Logging out invalidates the session and regenerates the CSRF token.

## Password reset

1. The member enters their email address on "Forgot your password?".
2. If the account exists, a reset link with a one-time token is emailed. Tokens expire after 60 minutes (`config/auth.php`).
3. The member chooses a new password.

Requests for reset links are limited to 6 a minute.

## Password rules

`AppServiceProvider` sets the rules used wherever a password is chosen:

- always at least 8 characters;
- in production also mixed case, at least one number, and not found in known data breaches.

## Sessions and cookies

| Setting | Value | Why |
| --- | --- | --- |
| Storage | Database (`sessions` table) | Works without extra services |
| `http_only` | `true` | JavaScript cannot read the session cookie |
| `same_site` | `lax` | The cookie is not sent with cross-site form posts |
| `secure` | `true` in production | The cookie is sent over HTTPS only |
| Lifetime | 120 minutes of inactivity | |

Every form is protected against cross-site request forgery by Laravel's token check, which Inertia handles automatically.

## Account settings

`/profile` lets a member change their name, username, bio, photo, email and password, and delete their account (the current password is required). The account being changed is always the signed-in one; no user id is read from the request.

## Guests

People without an account can still comment on recipes, react to them, reply in the forum and report content. They are recognised by an anonymous cookie, described in [security.md](security.md).

## Development accounts

`DevelopmentUserSeeder` creates one account per role with the password `password`. It is skipped when `APP_ENV=production`.
