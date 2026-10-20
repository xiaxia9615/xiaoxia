<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Strategies;

use App\Modules\AiProvider\Models\AiProvider;
use App\Modules\AiProvider\Services\CircuitBreaker;
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
     * @return array<string, mixed>
     */
    public function select(): array
    {
        $best = null;
        $bestCost = null;
        AiProvider::query()
            ->where('enabled', true)
            ->get()
            ->each(function (AiProvider $provider) use (&$best, &$bestCost): void {
                // 熔断过滤：熔断中的 Provider 直接跳过，不参与路由
                if (! $this->circuitBreaker->isAvailable((int) $provider->id)) {
                    return;
                }

                $totalCost = (float) $provider->cost_per_1k_input
                    + (float) $provider->cost_per_1k_output;
                if ($bestCost === null || bccomp((string) $totalCost, (string) $bestCost, 6) < 0) {
                    $bestCost  = $totalCost;
                    $best = [
                        'id'                => $provider->id,
                        'name'              => $provider->name,
                        'display_name'      => $provider->display_name,
                        'cost_per_1k_input' => (float) $provider->cost_per_1k_input,
                        'cost_per_1k_output' => (float) $provider->cost_per_1k_output,
                        'total_cost_per_1k' => $totalCost,
                        // 数据驱动字段：供客户端工厂按 Provider 配置实例化客户端
                        'driver'            => $provider->driver,
                        'model_name'        => $provider->model_name,
                        'base_url'          => $provider->base_url,
                        'api_key'           => $provider->api_key,
                    ];
                }
            });
        if ($best === null) {
            // 所有 Provider 均被熔断（或无可用 Provider）
            throw new RuntimeException('所有 AI 服务暂不可用，请稍后再试');
        }
        return $best;
    }
}
