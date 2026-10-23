<?php

declare(strict_types=1);

namespace App\Modules\AiProvider\Actions;

use App\Modules\AiProvider\Strategies\LowestCostStrategy;

final class SelectBestProviderAction
{
    public function __construct(
        private readonly LowestCostStrategy $strategy,
    ) {}

    /**
     * 选择综合成本最低的 Provider，返回数组形式信息（严禁返回 Model）
     *
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        return $this->strategy->select();
    }
}
