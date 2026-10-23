<?php

declare(strict_types=1);

namespace App\Modules\Team\Dto;

/**
 * 团队计费上下文：计费服务只认本契约。
 *
 * actor_user_id 只能来自成员子 Key 映射或服务端注入，禁止从请求体读取。
 */
final class BillingContext
{
    public function __construct(
        public readonly int $teamId,
        public readonly int $actorUserId,
        public readonly int $billingOwnerId,
        public readonly int $apiKeyId,
        public readonly string $requestId,
        public readonly string $billingScope = 'team',
    ) {}
}
