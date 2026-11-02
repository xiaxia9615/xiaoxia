<?php

declare(strict_types=1);

namespace App\Modules\Skill\Actions;

use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Skill\Enums\SkillStatus;
use App\Modules\Skill\Models\SkillDetail;

final class SubmitSkillAction
{
    public function __construct(
        private readonly SkillProductContract $products,
    ) {}

    public function execute(int $skillId, int $userId): array
    {
        $detail = SkillDetail::query()->find($skillId);
        if ($detail === null) abort(404, '技能不存在');

        $product = $this->products->findSkill($skillId);
        
        // 最严厉的权限拦截
        if (!$product || (int)($product['user_id'] ?? 0) !== (int)$userId) {
            abort(403, '无权操作他人技能');
        }

        $detail->update(['status' => SkillStatus::Pending->value]);

        return ['id' => (int) $detail->product_id, 'status' => SkillStatus::Pending->value];
    }
}
