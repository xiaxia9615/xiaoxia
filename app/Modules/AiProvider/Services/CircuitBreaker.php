<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Services;

use Illuminate\Support\Facades\Cache;

/**
 * 熔断器（Circuit Breaker）：基于 Cache 实现（生产环境走 Redis）。
 *
 * 某个 Provider 连续失败达到阈值（3 次）后被熔断 60 秒：
 * 期间路由策略不再选中它，优先路由给备用模型；
 * 60 秒后熔断标记自动过期，恢复试探。
 */
final class CircuitBreaker
{
    /** 连续失败多少次后触发熔断 */
    private const FAILURE_THRESHOLD = 3;

    /** 熔断持续时间（秒），到期自动恢复试探 */
    private const OPEN_SECONDS = 60;

    private const KEY_OPEN_PREFIX       = 'circuit_breaker:provider:';
    private const KEY_FAIL_COUNT_PREFIX = 'circuit_breaker:fail_count:';

    /**
     * Provider 是否可用：熔断标记存在则不可用。
     */
    public function isAvailable(int $providerId): bool
    {
        return ! Cache::has(self::KEY_OPEN_PREFIX.$providerId);
    }

    /**
     * 记录一次失败：失败计数 +1，达到阈值（3 次）后写入熔断标记（60 秒），并清空计数。
     */
    public function recordFailure(int $providerId): void
    {
        $failCountKey = self::KEY_FAIL_COUNT_PREFIX.$providerId;

        $failures = (int) Cache::increment($failCountKey);

        if ($failures >= self::FAILURE_THRESHOLD) {
            // 写入熔断标记：60 秒内该 Provider 被踢出路由池
            Cache::put(self::KEY_OPEN_PREFIX.$providerId, true, self::OPEN_SECONDS);
            // 清空失败计数，进入下一轮统计
            Cache::forget($failCountKey);
        }
    }

    /**
     * 记录一次成功：清空失败计数（如有积攒）。
     */
    public function recordSuccess(int $providerId): void
    {
        Cache::forget(self::KEY_FAIL_COUNT_PREFIX.$providerId);
    }
}
