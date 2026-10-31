<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Contracts\Events;

final readonly class AiUsageRecorded
{
    public function __construct(
        public int $userId,
        public int $providerId,
        public float $cost,
        public int $inputTokens,
        public int $outputTokens,
    ) {}
}
