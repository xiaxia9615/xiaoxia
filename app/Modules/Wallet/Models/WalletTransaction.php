<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $wallet_id
 * @property string|null $out_trade_no
 * @property int $amount
 * @property string $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \App\Modules\Wallet\Models\Wallet|null $wallet
 */
class WalletTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
