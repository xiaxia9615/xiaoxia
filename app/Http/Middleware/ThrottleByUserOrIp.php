<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class ThrottleByUserOrIp
{
    public function handle(Request $request, Closure $next, string $configKey): SymfonyResponse
    {
        $maxAttempts = (int) config("ratelimit.{$configKey}");

        if ($maxAttempts <= 0) {
            return $next($request);
        }

        $user = $request->user();
        $limiterKey = $user !== null
            ? $configKey.'|user:'.$user->getAuthIdentifier()
            : $configKey.'|ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $retryAfter = max(1, RateLimiter::availableIn($limiterKey));

            return response()->json([
                'code' => 429,
                'message' => __('messages.rate_limited'),
                'retry_after' => $retryAfter,
            ], 429, ['Retry-After' => (string) $retryAfter]);
        }

        RateLimiter::hit($limiterKey);

        return $next($request);
    }
}
