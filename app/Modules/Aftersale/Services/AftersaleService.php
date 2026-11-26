<?php

declare(strict_types=1);

namespace App\Modules\Aftersale\Services;

use App\Modules\Aftersale\Models\Aftersale;
use App\Modules\Order\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AftersaleService
{
    /**
     * 用户售后申请列表（分页，联查订单信息）.
     */
    public function paginate(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Aftersale::query()
            ->with('order:id,order_no,product_name,amount,status')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 创建售后申请.
     *
     * @param  array{order_id:int, type:int, reason:string, description?:string}  $data
     */
    public function create(int $userId, array $data): Aftersale
    {
        $order = Order::query()
            ->where('id', $data['order_id'])
            ->where('user_id', $userId)
            ->first();

        if ($order === null) {
            throw new RuntimeException('订单不存在或不属于当前用户');
        }

        if (! in_array($order->status, ['paid', 'completed'], true)) {
            throw new RuntimeException('当前订单状态不支持售后申请');
        }

        // 检查是否已有进行中的售后申请
        $existing = Aftersale::query()
            ->where('order_id', $order->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            throw new RuntimeException('该订单已有进行中的售后申请');
        }

        return DB::transaction(function () use ($order, $userId, $data): Aftersale {
            return Aftersale::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'order_no' => $order->order_no,
                'type' => $data['type'],
                'status' => 'pending',
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'refund_amount' => (int) $order->amount,
            ]);
        });
    }

    /**
     * 取消售后申请（仅 pending 状态可取消）.
     */
    public function cancel(int $userId, int $aftersaleId): bool
    {
        $aftersale = Aftersale::query()
            ->where('id', $aftersaleId)
            ->where('user_id', $userId)
            ->first();

        if ($aftersale === null) {
            throw new RuntimeException('售后申请不存在');
        }

        if ($aftersale->status !== 'pending') {
            throw new RuntimeException('仅待处理的申请可取消');
        }

        $aftersale->status = 'cancelled';
        return $aftersale->save();
    }
}
