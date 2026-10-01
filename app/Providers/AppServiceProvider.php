<?php

namespace App\Providers;

use App\Models\Report;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Store short names such as "recipe" (not PHP class names) in the
        // database for reported content and moderation log entries.
        Relation::morphMap(Report::REPORTABLE_TYPES);

        $this->configureRateLimiting();
        $this->addSearchHelpers();
        $this->configureSafetyDefaults();
    }

    /**
     * Settings that catch mistakes early and keep production strict.
     */
    private function configureSafetyDefaults(): void
    {
        // While developing and testing, Eloquent throws an error instead of
        // quietly running a query per row (lazy loading in a loop), or
        // quietly ignoring a field that is not fillable.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Every place that asks for Password::defaults() gets these rules.
        Password::defaults(function () {
            $rule = Password::min(8);

            // On the live site, also require mixed case and a number, and
            // refuse passwords known from data breaches.
            return $this->app->isProduction()
                ? $rule->mixedCase()->numbers()->uncompromised()
                : $rule;
        });

        // Generate https:// links on the live site.
        URL::forceHttps($this->app->isProduction());
    }

    /**
     * Add ->whereContains('title', $words) and ->orWhereContains(...) to
     * every database query. They find rows where the column contains the text.
     *
     * Two things make them safe to use with whatever a visitor typed:
     * - the text is sent to the database as a bound parameter, never as SQL;
     * - the LIKE wildcards % and _ are escaped (with "!"), so they match
     *   themselves instead of "anything". This works on MySQL and SQLite.
     */
    private function addSearchHelpers(): void
    {
        Builder::macro('whereContains', function (string $column, string $text, string $boolean = 'and') {
            /** @var Builder $this */
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text).'%';

            return $this->whereRaw($this->grammar->wrap($column)." like ? escape '!'", [$pattern], $boolean);
        });

        Builder::macro('orWhereContains', function (string $column, string $text) {
            /** @var Builder $this */
            return $this->whereContains($column, $text, 'or');
        });
    }

    /**
     * How often people may post. Routes opt in with ->middleware('throttle:name').
     *
     * Members are counted per account. Guests are counted per IP address and
     * get much lower limits. The IP address is only used as a short-lived
     * counter key, which Laravel hashes; it is never written to our tables.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('comments', function (Request $request) {
            if ($request->user() !== null) {
                return Limit::perMinute(10)->by('comments:user:'.$request->user()->id);
            }

            return [
                Limit::perMinute(3)->by('comments:guest-minute:'.$request->ip()),
                Limit::perHour(20)->by('comments:guest-hour:'.$request->ip()),
            ];
        });

        // Forum topics and replies. Guests get the strictest limits of all.
        RateLimiter::for('forum-posts', function (Request $request) {
            if ($request->user() !== null) {
                return Limit::perMinute(10)->by('forum:user:'.$request->user()->id);
            }

            return [
                Limit::perMinute(2)->by('forum:guest-minute:'.$request->ip()),
                Limit::perHour(10)->by('forum:guest-hour:'.$request->ip()),
            ];
        });

        RateLimiter::for('reports', function (Request $request) {
            return $request->user() !== null
                ? Limit::perMinute(10)->by('reports:user:'.$request->user()->id)
                : [
                    Limit::perMinute(3)->by('reports:guest-minute:'.$request->ip()),
                    Limit::perHour(10)->by('reports:guest-hour:'.$request->ip()),
                ];
        });

        RateLimiter::for('reactions', function (Request $request) {
            return $request->user() !== null
                ? Limit::perMinute(60)->by('reactions:user:'.$request->user()->id)
                : Limit::perMinute(20)->by('reactions:guest:'.$request->ip());
        });
    }
}
