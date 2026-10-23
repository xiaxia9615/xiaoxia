<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Strategies;

use App\Modules\AiProvider\Models\AiProvider;
use App\Modules\AiProvider\Services\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class LowestCostStrategy
{
    public function __construct(
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    /**
     * 动态读取所有启用 Provider 的成本数据，计算综合成本（每千 token 输入+输出成本之和），
     * 返回成本最低的 Provider 信息数组（严禁返回 Model）。
     * 处于熔断状态的 Provider 会被剔除，不参与路由；
     * 若所有 Provider 均被熔断，抛出 RuntimeException。
     *
     * 启用中的 Provider 列表缓存 10 分钟（避免每次请求全表扫描 ai_providers 表），
     * 熔断状态不缓存、实时读取，保证故障切换及时生效。
     *
     * @return array<string, mixed>
     */
    public function select(): array
    {
        $best = null;
        $bestCost = null;

        // 生产环境：Provider 列表缓存 600 秒；测试环境直查，避免用例间数据污染
        $providers = app()->environment('testing')
            ? $this->fetchActiveProviders()
            : Cache::remember('active_ai_providers', 600, fn (): array => $this->fetchActiveProviders());

        foreach ($providers as $provider) {
            // 熔断过滤：熔断中的 Provider 直接跳过，不参与路由（实时判断，不受列表缓存影响）
            if (! $this->circuitBreaker->isAvailable($provider['id'])) {
                continue;
            }

            $totalCost = $provider['cost_per_1k_input']
                + $provider['cost_per_1k_output'];
            if ($bestCost === null || bccomp((string) $totalCost, (string) $bestCost, 6) < 0) {
                $bestCost = $totalCost;
                $best = [
                    'id' => $provider['id'],
                    'name' => $provider['name'],
                    'display_name' => $provider['display_name'],
                    'cost_per_1k_input' => $provider['cost_per_1k_input'],
                    'cost_per_1k_output' => $provider['cost_per_1k_output'],
                    'total_cost_per_1k' => $totalCost,
                    // 数据驱动字段：供客户端工厂按 Provider 配置实例化客户端
                    'driver' => $provider['driver'],
                    'model_name' => $provider['model_name'],
                    'base_url' => $provider['base_url'],
                    'api_key' => $provider['api_key'],
                ];
            }
        }

        if ($best === null) {
            // 所有 Provider 均被熔断（或无可用 Provider）
            throw new RuntimeException('所有 AI 服务暂不可用，请稍后再试');
        }

        return $best;
    }

    /**
     * 查询启用中的 Provider，返回纯数组（严禁返回 Model）。
     *
     * @return list<array<string, mixed>>
     */
    private function fetchActiveProviders(): array
    {
        return AiProvider::query()
            ->where('enabled', true)
            ->get()
            ->map(fn (AiProvider $provider): array => [
                'id' => (int) $provider->id,
                'name' => (string) $provider->name,
                'display_name' => (string) $provider->display_name,
                'cost_per_1k_input' => (float) $provider->cost_per_1k_input,
                'cost_per_1k_output' => (float) $provider->cost_per_1k_output,
                'driver' => (string) $provider->driver,
                'model_name' => (string) $provider->model_name,
                'base_url' => (string) $provider->base_url,
                'api_key' => (string) $provider->api_key,
            ])
            ->all();
    }
}
