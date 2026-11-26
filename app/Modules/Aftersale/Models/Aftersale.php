<?php

declare(strict_types=1);

namespace App\Modules\Aftersale\Models;

use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property string $order_no
 * @property int $type
 * @property string $status
 * @property string $reason
 * @property string|null $description
 * @property int $refund_amount
 * @property string|null $admin_note
 * @property Carbon|null $processed_at
 */
class Aftersale extends Model
{
    protected $table = 'aftersales';

    protected $fillable = [
        'order_id', 'user_id', 'order_no', 'type', 'status',
        'reason', 'description', 'refund_amount', 'admin_note', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'user_id' => 'integer',
            'type' => 'integer',
            'refund_amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
