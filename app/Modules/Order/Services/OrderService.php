<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Distributor\Models\DistributorRelation;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Product\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class OrderService
{
    /**
     * 订单号唯一键冲突时的最大重试次数
     */
    private const ORDER_NO_MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly LedgerContract $ledger,
    ) {}

    /**
     * 创建订单：不扣款，仅生成待支付订单并快照商品价格与信息
     *
     * @throws RuntimeException 商品不存在或未上架
     */
    public function createOrder(int $userId, int $productId): Order
    {
        $product = Product::query()->find($productId);

        if (! $product || $product->status !== 1) {
            throw new RuntimeException('商品不存在或未上架');
        }

        return $this->insertWithOrderNoRetry(function () use ($product, $userId): Order {
            return Order::create([
                'order_no' => $this->generateOrderNo(),
                'user_id' => $userId,
                'product_id' => $product->id,
                'amount' => (int) $product->price,
                'product_name' => $product->name,
                'product_snapshot' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (int) $product->price,
                    'type' => (int) $product->type,
                    'description' => $product->description,
                ],
                'status' => OrderStatus::Pending,
            ]);
        });
    }

    /**
     * 支付订单（模拟支付）：
     * 1. 事务内悲观锁（lockForUpdate）锁定订单行；
     * 2. 已支付订单幂等返回，严禁重复扣款；
     * 3. 仅待支付订单可支付；
     * 4. 严格按订单 amount（价格快照）扣款，绝不读商品最新价格；
     * 5. 扣款成功后置为 Paid，并按比例分账（开发者/达人/平台）。
     *
     * @return array{status: string, order: Order, message?: string}
     *
     * @throws RuntimeException 订单不存在、状态非法或余额不足
     */
    public function payOrder(int $orderId, int $userId): array
    {
        return DB::transaction(function () use ($orderId, $userId): array {
            /** @var Order|null $order */
            $order = Order::query()
                ->where('id', $orderId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException('订单不存在');
            }

            // 幂等：已支付订单直接返回，严禁重复扣款
            if ($order->status === OrderStatus::Paid) {
                return ['status' => 'already_paid', 'order' => $order, 'message' => '订单已支付'];
            }

            if (! $order->status->canPay()) {
                throw new RuntimeException('当前订单状态不可支付');
            }

            $buyer = $order->user;
            if (! $buyer) {
                throw new RuntimeException('买家不存在');
            }

            // 按订单价格快照从账本原子扣款（悲观锁 + 余额校验），重复支付由上方订单状态幂等拦截
            $this->ledger->debit('user', $buyer->id, 'balance', $order->amount, 'order_pay', $order->id);

            $order->status = OrderStatus::Paid;
            $order->paid_at = now();
            $order->save();

            // 分账：开发者 80%；买家绑定上级达人时达人 10%、平台 10%，否则平台 20%；误差归平台
            $this->settleCommission($order);

            Log::info('order paid, commission settled', [
                'order_no' => $order->order_no,
                'amount' => $order->amount,
            ]);

            return ['status' => 'success', 'order' => $order];
        });
    }

    /**
     * 取消订单：仅待支付订单可取消
     *
     * @throws RuntimeException 订单不存在或状态不可取消
     */
    public function cancelOrder(int $orderId, int $userId): Order
    {
        return DB::transaction(function () use ($orderId, $userId): Order {
            /** @var Order|null $order */
            $order = Order::query()
                ->where('id', $orderId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new RuntimeException('订单不存在');
            }

            if (! $order->status->canCancel()) {
                throw new RuntimeException('当前订单状态不可取消');
            }

            $order->status = OrderStatus::Cancelled;
            $order->save();

            return $order;
        });
    }

    /**
     * 订单分账（在 payOrder 事务内执行，与扣款原子提交）：
     * - 无达人：开发者 80%，平台 20%；
     * - 有达人：开发者 80%，达人 10%，平台 10%；
     * - 金额单位为毫，向下取整，舍入误差归入平台；
     * - 幂等：事务内先查 order_commission 流水，已分账订单直接跳过，防止重复分账。
     */
    private function settleCommission(Order $order): void
    {
        // 幂等：同一订单的分账流水已存在则跳过（payOrder 已对订单行加悲观锁，此处双保险）
        $settled = DB::table('ledger_entries')
            ->where('biz_type', 'order_commission')
            ->where('biz_id', $order->id)
            ->exists();
        if ($settled) {
            return;
        }

        $amount = (int) $order->amount;

        // 开发者分成 80%，向下取整
        $developerShare = intdiv($amount * 80, 100);

        // 买家绑定的上级达人（一个用户仅绑定一个达人）
        $distributorId = (int) DistributorRelation::query()
            ->where('user_id', $order->user_id)
            ->value('distributor_id');

        // 复式记账：支付时货款已全额计入平台账户，分账先从平台转出，再分给各方，账目保持平衡
        $splits = [
            ['owner_type' => 'platform', 'owner_id' => 0, 'account_type' => 'platform', 'direction' => 'debit', 'amount' => $amount],
        ];

        $paidOut = 0;

        $developerId = $this->developerIdOf($order);
        if ($developerId > 0) {
            $splits[] = ['owner_type' => 'user', 'owner_id' => $developerId, 'account_type' => 'balance', 'direction' => 'credit', 'amount' => $developerShare];
            $paidOut += $developerShare;
        }

        if ($distributorId > 0) {
            $distributorShare = intdiv($amount * 10, 100);
            $splits[] = ['owner_type' => 'user', 'owner_id' => $distributorId, 'account_type' => 'balance', 'direction' => 'credit', 'amount' => $distributorShare];
            $paidOut += $distributorShare;
        }

        // 平台分成 = 总额 - 开发者 - 达人，舍入误差自然归入平台
        $splits[] = ['owner_type' => 'platform', 'owner_id' => 0, 'account_type' => 'platform', 'direction' => 'credit', 'amount' => $amount - $paidOut];

        $this->ledger->record('order_commission', $order->id, $splits);
    }

    /**
     * 取订单开发者（商品提供者）用户ID，商品软删除后仍可追溯
     */
    private function developerIdOf(Order $order): int
    {
        $product = Product::withTrashed()->find($order->product_id);

        return (int) ($product->user_id ?? 0);
    }

    /**
     * 生成订单号：日期(YYYYMMDD) + 8位随机数字序列
     */
    private function generateOrderNo(): string
    {
        return now()->format('Ymd').str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * 插入订单，订单号唯一键冲突时自动重试生成新订单号
     *
     * @param  callable(): Order  $insert
     *
     * @throws RuntimeException 重试次数耗尽仍冲突
     */
    private function insertWithOrderNoRetry(callable $insert): Order
    {
        $attempts = 0;

        do {
            $attempts++;
            try {
                return $insert();
            } catch (QueryException $e) {
                // 23000: 唯一键约束冲突（SQLite/MySQL 通用 SQLSTATE）
                if ($e->getCode() !== '23000' || $attempts >= self::ORDER_NO_MAX_ATTEMPTS) {
                    throw $e;
                }
            }
        } while (true);
    }
}
