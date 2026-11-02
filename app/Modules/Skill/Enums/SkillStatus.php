<?php

declare(strict_types=1);

namespace App\Modules\Skill\Enums;

enum SkillStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case Archived = 'archived';

    /**
     * 是否可提交审核（仅草稿状态可提审）
     */
    public function canSubmit(): bool
    {
        return $this === self::Draft;
    }

    /**
     * 是否可被管理员审核（仅待审核状态可审核）
     */
    public function canAudit(): bool
    {
        return $this === self::Pending;
    }
}
