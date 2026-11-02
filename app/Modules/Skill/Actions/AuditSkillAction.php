<?php

declare(strict_types=1);

namespace App\Modules\Skill\Actions;

use App\Modules\Product\Contracts\SkillProductContract;
use App\Modules\Skill\Enums\SkillStatus;
use App\Modules\Skill\Models\SkillDetail;
use RuntimeException;

final class AuditSkillAction
{
    public function __construct(
        private readonly SkillProductContract $products,
    ) {}

    /**
     * 管理员审核技能（状态改为 PUBLISHED 或 REJECTED）
     *
     * @return array{id: int, status: int, skill_status: string}
     */
    public function execute(int $id, bool $approve, ?string $note): array
    {
        $result = $this->products->auditSkill($id, $approve, $note);

        if ($result === null) {
            throw new RuntimeException("Skill not found: {$id}");
        }

        /** @var SkillDetail|null $detail */
        $detail = SkillDetail::query()->where('product_id', $id)->first();

        if ($detail !== null) {
            $detail->update([
                'status' => $approve ? SkillStatus::Published->value : SkillStatus::Rejected->value,
            ]);
        }

        return [
            'id' => $result['id'],
            'status' => $result['status'],
            'skill_status' => $approve ? SkillStatus::Published->value : SkillStatus::Rejected->value,
        ];
    }
}
