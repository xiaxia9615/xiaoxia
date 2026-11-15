<?php

declare(strict_types=1);

namespace App\Modules\Order\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case PendingShipment = 'pending_shipment';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Refunded = 'refunded';

    /**
     * 是否可支付：仅待支付订单可支付
     */
    public function canPay(): bool
    {
        return $this === self::Pending;
    }

    /**
     * 是否可取消：仅待支付订单可取消
     */
    public function canCancel(): bool
    {
        return $this === self::Pending;
    }

    /**
     * 是否可退款：已支付或待发货订单可退款（已交付/已完成的虚拟订单不可退款）
     */
    public function canRefund(): bool
    {
        return $this === self::Paid || $this === self::PendingShipment;
    }

    /**
     * 中文标签
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '待支付',
            self::Paid => '已支付',
            self::PendingShipment => '待发货',
            self::Cancelled => '已取消',
            self::Completed => '已完成',
            self::Refunded => '已退款',
        };
    }
}
