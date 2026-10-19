<?php
declare(strict_types=1);
namespace App\Modules\AiProvider\Strategies;
use App\Modules\AiProvider\Models\AiProvider;
use RuntimeException;
final class LowestCostStrategy
{
    /**
     * 动态读取所有启用 Provider 的成本数据，计算综合成本（每千 token 输入+输出成本之和），
     * 返回成本最低的 Provider 信息数组（严禁返回 Model）。
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
                    ];
                }
            });
        if ($best === null) {
            throw new RuntimeException('没有可用的 AI Provider');
        }
        return $best;
    }
}
