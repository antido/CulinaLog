<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds security headers to every page.
 *
 * The most important one is the Content Security Policy (CSP). It tells the
 * browser which scripts, styles, images and connections this site uses, and
 * to refuse everything else. If a flaw ever let someone inject a <script>
 * into a page, the browser would still not run it, because every real script
 * tag carries a random "nonce" that changes on each request.
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Must happen before the page is rendered, so that @vite, @routes
        // and the structured-data script all print the same nonce.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            // Tell browsers to use HTTPS for this site for the next year.
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($this->shouldSendContentSecurityPolicy($response)) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));
        }

        return $response;
    }

    /**
     * The policy is skipped in two development-only situations where it
     * would only get in the way:
     * - `npm run dev` is running, so scripts come from the Vite dev server;
     * - Laravel is showing its detailed error page (APP_DEBUG=true), which
     *   uses inline scripts of its own.
     */
    private function shouldSendContentSecurityPolicy(Response $response): bool
    {
        if (Vite::isRunningHot()) {
            return false;
        }

        return ! (config('app.debug') && $response->getStatusCode() >= 500);
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            // Only our own script files and inline scripts carrying this request's nonce.
            "script-src 'self' 'nonce-{$nonce}'",
            // Styles: our stylesheet, the font provider's stylesheet, and the small
            // inline styles that the dialog and progress-bar libraries add.
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net",
            // Images: our own, plus the in-browser preview of a chosen upload.
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            // No plugins, no embedding this site in a frame, forms post only to us.
            "object-src 'none'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
