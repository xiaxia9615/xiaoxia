<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\ThrottleByUserOrIp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'throttle.user_or_ip' => ThrottleByUserOrIp::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // 5xx 级异常：通过 Log::withContext 补充 user_id 与 request_id 追踪上下文。
        // 返回 true 保持框架默认日志记录，不重复记录，也不阻止默认报告。
        $exceptions->reportable(function (Throwable $e): bool {
            // 非 5xx 的 HTTP 异常（401/403/404/429 等 4xx）不记录 error 日志，避免污染磁盘
            if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
                return false;
            }

            // CLI（队列/定时任务）无 HTTP 请求上下文，直接交回默认日志
            if (app()->runningInConsole()) {
                return true;
            }

            $request = request();
            if ($request === null) {
                return true;
            }

            $context = [];

            $user = $request->user();
            if ($user !== null) {
                $context['user_id'] = $user->getAuthIdentifier();
            }

            $requestId = $request->header('X-Request-Id');
            if (! is_string($requestId) || $requestId === '') {
                $requestId = (string) Str::uuid();
            }
            $context['request_id'] = $requestId;

            Log::withContext($context);

            return true;
        });
    })->create();
