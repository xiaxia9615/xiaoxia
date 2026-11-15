<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $balance
 * @property int $frozen_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Wallet extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'frozen_balance' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
