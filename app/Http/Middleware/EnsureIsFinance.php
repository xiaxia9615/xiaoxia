<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 财务中心权限中间件：仅超级管理员（is_super_admin）与财务人员（is_finance）可访问。
 */
final class EnsureIsFinance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->is_super_admin && ! $user->is_finance)) {
            abort(403, 'Unauthorized access to Finance.');
        }

        return $next($request);
    }
}
