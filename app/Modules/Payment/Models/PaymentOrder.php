<?php

declare(strict_types=1);

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $order_no
 * @property int $amount_in_cents
 * @property string $gateway
 * @property string $status
 * @property array|null $gateway_payload
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class PaymentOrder extends Model
{
    protected $fillable = [
        'user_id',
        'order_no',
        'amount_in_cents',
        'gateway',
        'status',
        'gateway_payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'gateway_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }
}
