<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Listeners;

use App\Modules\Ledger\Services\LedgerService;
use App\Modules\User\Events\UserRegistered;

final readonly class CreateLedgerOnUserRegistered
{
    public function __construct(
        private LedgerService $ledger,
    ) {}

    public function handle(UserRegistered $event): void
    {
        // 注册送 100 元体验金（10000 分）
        $this->ledger->record('signup_bonus', $event->user->id, [
            [
                'owner_type'   => 'platform',
                'owner_id'     => 0,
                'account_type' => 'platform',
                'direction'    => 'debit',
                'amount'       => 100000,
            ],
            [
                'owner_type'   => 'user',
                'owner_id'     => $event->user->id,
                'account_type' => 'balance',
                'direction'    => 'credit',
                'amount'       => 100000,
            ],
        ]);
    }
}
