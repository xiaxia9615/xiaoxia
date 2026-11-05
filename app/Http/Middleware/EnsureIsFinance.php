<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
