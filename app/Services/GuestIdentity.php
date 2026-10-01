<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Recognises a guest (someone without an account) across requests,
 * without storing anything personal about them.
 *
 * How it works:
 * - The first time a guest comments or reacts, they receive a long random
 *   token in a cookie. Laravel encrypts and signs cookies, JavaScript cannot
 *   read this one (HttpOnly), and it is sent over HTTPS only in production.
 * - The database stores only a SHA-256 hash of the token. Someone who reads
 *   the database cannot turn a hash back into a token and pose as the guest.
 * - No IP address or browser detail is stored.
 */
class GuestIdentity
{
    public const COOKIE = 'guest_token';

    private const LIFETIME_MINUTES = 60 * 24 * 365;

    /**
     * The hash for a guest who already has a token, or null for a new visitor.
     * Use this when only reading, so page views do not hand out cookies.
     */
    public function existingHash(Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        return $this->isValid($token) ? hash('sha256', $token) : null;
    }

    /**
     * The hash for this guest, giving them a new token first if needed.
     * Use this when the guest is saving something.
     */
    public function hash(Request $request): string
    {
        $token = $request->cookie(self::COOKIE);

        if (! $this->isValid($token)) {
            $token = Str::random(64);

            Cookie::queue(cookie(
                name: self::COOKIE,
                value: $token,
                minutes: self::LIFETIME_MINUTES,
                secure: config('session.secure'),
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return hash('sha256', $token);
    }

    private function isValid(mixed $token): bool
    {
        return is_string($token) && strlen($token) === 64 && ctype_alnum($token);
    }
}
