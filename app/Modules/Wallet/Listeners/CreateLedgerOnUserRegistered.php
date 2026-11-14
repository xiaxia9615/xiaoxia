<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Listeners;

use App\Modules\User\Contracts\Events\UserRegistered;
use App\Modules\Wallet\Services\WalletService;

final readonly class CreateLedgerOnUserRegistered
{
    public function __construct(
        private WalletService $wallet,
    ) {}

    public function handle(UserRegistered $event): void
    {
        // 注册送 100 元体验金（10000 分）
        $this->wallet->record('signup_bonus', $event->user->id, [
            [
                'owner_type' => 'platform',
                'owner_id' => 0,
                'account_type' => 'platform',
                'direction' => 'debit',
                'amount' => 100000,
            ],
            [
                'owner_type' => 'user',
                'owner_id' => $event->user->id,
                'account_type' => 'balance',
                'direction' => 'credit',
                'amount' => 100000,
            ],
        ]);
    }
}
