<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Runs for every request, including "page not found", so that
        // error pages get the security headers too.
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Route middleware from Spatie Laravel Permission,
        // for example ->middleware('permission:users.manage').
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /*
         * Friendly error handling. This runs after Laravel has turned an
         * exception into a response, and may replace that response.
         */
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if ($request->expectsJson()) {
                return $response;
            }

            // Posting too quickly from inside the app: go back with a message.
            if ($status === 429 && $request->hasHeader('X-Inertia')) {
                return back()->with('error', 'You are doing that too often. Wait a minute, then try again.');
            }

            // The form's security token expired (the page was open for a long time).
            if ($status === 419) {
                return back()->with('error', 'The page expired. Please try again.');
            }

            // "Forbidden", "not found" and "too many requests" always get our own page.
            // Server errors get it only when APP_DEBUG is off, so developers still
            // see the detailed error screen locally, and visitors never do.
            $showOwnPage = in_array($status, [403, 404, 429], true)
                || (in_array($status, [500, 503], true) && ! config('app.debug'));

            if ($showOwnPage) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
