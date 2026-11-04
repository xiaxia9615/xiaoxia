<?php

declare(strict_types=1);

namespace App\Modules\Order\Models;

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'user_id',
        'product_id',
        'amount',
        'product_name',
        'product_snapshot',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'product_snapshot' => 'array',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    protected static function newFactory()
    {
        return OrderFactory::new();
    }

    /**
     * 买家
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 商品（基于快照，历史订单不受商品后续修改影响）
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
