<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 未认证请求交由后续 auth 中间件处理
        if ($user !== null && (! $user->is_active || (int) $user->status !== 1)) {
            abort(403, '账号已被禁用');
        }

        return $next($request);
    }
}
