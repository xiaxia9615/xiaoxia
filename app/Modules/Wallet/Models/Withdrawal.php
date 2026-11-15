<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $amount
 * @property Carbon|null $audited_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $created_at
 */
class Withdrawal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PAID = 'paid';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'audited_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
