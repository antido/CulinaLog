# Security

This page lists the protections in CulinaLog, the threat each one answers, and where the code is. The tests in `tests/Feature/SecurityTest.php` and the per-module test files check them.

## Summary

| Threat | Protection | Where |
| --- | --- | --- |
| Cross-site scripting (XSS) | User text is shown as plain text; a Content Security Policy blocks injected scripts | React pages, `SecurityHeaders` |
| Cross-site request forgery (CSRF) | A token is required on every form post | Laravel's `web` middleware group |
| SQL injection | All queries use bound parameters | Eloquent, `whereContains` in `AppServiceProvider` |
| Mass assignment | Sensitive columns are not fillable | `#[Fillable]` on each model |
| Accessing other people's records (IDOR) | Policies compare the record's owner to the signed-in user | `app/Policies` |
| Broken access control | Permission middleware plus policies on every protected route | `routes/admin.php`, `routes/moderation.php` |
| Privilege escalation | Roles are never read from a request; nobody can change their own role | `RegisteredUserController`, `UserPolicy` |
| Malicious uploads | Images are decoded and re-saved under a random name | `ImageService` |
| Password guessing | Login lockout after 5 failures; throttled registration and reset | `LoginRequest`, `routes/auth.php` |
| Session theft | HttpOnly, SameSite and (in production) Secure cookies; new session ID at login | `config/session.php` |
| Spam and abuse | Rate limits, a spam trap, duplicate checks, approval queue | `AppServiceProvider`, comment and reply controllers |
| Information disclosure | Hand-picked fields per page; friendly error pages; 404 for private content | Model `toCard()` / `summary()` methods, `bootstrap/app.php` |

## XSS

- React escapes everything it prints. The code never uses `dangerouslySetInnerHTML`, so a comment containing `<script>` is displayed as those characters.
- The one place the server prints user text into a script tag is the recipe structured data in `resources/views/app.blade.php`. It is encoded with `JSON_HEX_TAG` and related flags, so text cannot close the tag.
- `SecurityHeaders` sends a **Content Security Policy**. Scripts run only from this site, and inline scripts only with a random value (a nonce) that changes on every request.

## Guest identity

Guests can comment, react, reply and report without giving any personal details.

- The first time a guest saves something, they receive a random 64-character token in a cookie named `guest_token`.
- Laravel encrypts and signs the cookie. It is HttpOnly (JavaScript cannot read it), SameSite=Lax, and HTTPS-only in production.
- The database stores only a **SHA-256 hash** of the token. Someone who reads the database cannot recover a token and pose as that guest.
- **No IP address is stored.** IP addresses are used only as short-lived rate-limit counter keys, which Laravel hashes.
- Viewing a page never sets the cookie; only saving does.
- Guests are shown as "Anonymous", or under a display name they typed on a recipe comment.

A guest who clears their cookies becomes a new guest. That is why guests also have strict rate limits.

## Spam controls

| Control | Detail |
| --- | --- |
| Rate limits | See the table below |
| Spam trap | Forms contain a hidden "website" field. Only automated scripts fill it in; those posts are silently discarded |
| Duplicate check | The same person cannot post the same text in the same place twice within 10 minutes |
| Link review | A guest comment containing a link waits for a moderator |
| Forum approval | Every guest forum reply waits for a moderator |
| Length limits | Comments 2,000 characters, replies 5,000, topics 10,000 |

### Rate limits

| Action | Members | Guests |
| --- | --- | --- |
| Recipe comments | 10 a minute | 3 a minute, 20 an hour |
| Forum topics and replies | 10 a minute | 2 a minute, 10 an hour |
| Reactions | 60 a minute | 20 a minute |
| Reports | 10 a minute | 3 a minute, 10 an hour |
| New recipes | 10 a minute | not allowed |
| Search | 60 a minute | 60 a minute |
| Registration, reset link | 6 a minute | 6 a minute |
| Login | 5 failures, then a lockout | |

## File uploads

`App\Services\ImageService` handles every upload (profile photos, category images, recipe photos).

1. Validation requires a real image of type JPEG, PNG or WebP, with a matching file extension, at most 5 MB and at most 6000 pixels on a side.
2. The file is decoded with PHP's GD library based on its actual contents.
3. It is resized and **written again as a new WebP file** with a random 40-character name.

The uploaded file itself is never stored, so a script hidden in an image, a misleading name such as `photo.php.jpg`, or metadata such as GPS location cannot survive. SVG, GIF and every non-image type are refused.

## Passwords and sessions

Covered in [authentication.md](authentication.md): hashed passwords, login lockout, session regeneration, secure cookies, and stronger password rules in production.

## Error pages

`bootstrap/app.php` replaces error responses with the app's own page (`resources/js/Pages/Error.jsx`).

- 403, 404 and 429 always use it.
- 500 and 503 use it whenever `APP_DEBUG=false`, so visitors never see a stack trace, file path or query.
- An expired form token (419) and a rate limit hit from inside the app return the person to their page with a message.

## HTTP headers

| Header | Value |
| --- | --- |
| `Content-Security-Policy` | Described above |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` (the site cannot be embedded in a frame) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | Camera, microphone and location are switched off |
| `Strict-Transport-Security` | Sent on HTTPS requests |

## Production checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] A fresh `APP_KEY` generated on the server
- [ ] The site is served over HTTPS only
- [ ] `SESSION_SECURE_COOKIE` is not set to `false`
- [ ] `MAIL_MAILER` is a real mail service, not `log`
- [ ] The web server's document root is the `public` folder
- [ ] The development accounts do not exist (they are not seeded in production)
- [ ] `LOG_LEVEL` is `warning` or `error`, and logs are not publicly reachable

## Known limits

- A guest who clears cookies can react again. Rate limits reduce this but do not remove it.
- Search uses `LIKE` matching. It is safe, but on a very large site a full-text index would be faster.
- The Content Security Policy allows inline styles, which the dialog and progress-bar libraries need.
- The policy is not sent while `npm run dev` is running, because scripts then come from the Vite dev server.

## Reporting a problem

If you find a security problem, tell the site owner privately rather than posting it publicly.
