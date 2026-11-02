<?php

declare(strict_types=1);

namespace App\Modules\User\Enums;

enum DeveloperStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * 是否可提交开发者申请（none 或 rejected 状态可重新申请）
     */
    public function canApply(): bool
    {
        return $this === self::None || $this === self::Rejected;
    }
}
