<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Modules\Distributor\Models\DistributorRelation;
use App\Modules\Ledger\Contracts\LedgerContract;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Product\Models\Product;
use App\Modules\Wallet\Contracts\WalletContract;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class OrderService
{
    /**
     * 订单号唯一键冲突时的最大重试次数
     */
    private const ORDER_NO_MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly WalletContract $wallet,
        private readonly LedgerContract $ledger,
        private readonly PaymentGatewayInterface $gateway,
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
                    'nature' => $product->nature,
                    'quota' => (int) $product->quota,
                    'skill_id' => $product->skill_id,
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
     * 5. 扣款成功后双态分流：实物商品进入待发货，虚拟商品自动发货后完成；
     * 6. 并按比例分账（开发者/达人/平台）。
     *
     * @return array{status: string, order: Order, message?: string}
     *
     * @throws RuntimeException 订单不存在、状态非法或余额不足
     */
    public function payOrder(int $orderId, int $userId): array
    {
        /** @var Order|null $order */
        $order = Order::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();

        if (! $order) {
            throw new RuntimeException('订单不存在');
        }

        // 幂等：已支付订单直接拒绝，严禁重复发起支付
        if (in_array($order->status, [OrderStatus::Paid, OrderStatus::PendingShipment, OrderStatus::Completed], true)) {
            throw new RuntimeException('订单已支付');
        }

        if (! $order->status->canPay()) {
            throw new RuntimeException('当前订单状态不可支付');
        }

        // 金额校验：支付宝最低 0.01 元 = 10 毫
        if ($order->amount < 10) {
            throw new RuntimeException('订单金额低于支付宝最低支付限额（0.01 元）');
        }

        // 获取商品名称作为支付标题
        /** @var Product|null $product */
        $product = Product::withTrashed()->find($order->product_id);
        $subject = $product !== null ? $product->name : '商品订单';

        // 调用真实支付宝网关，获取二维码（不在此时修改订单状态，由异步回调更新）
        $result = $this->gateway->createPayment(
            $order->order_no,
            (int) $order->amount,
            $subject,
        );

        Log::info('alipay payment created', [
            'order_no' => $order->order_no,
            'amount' => $order->amount,
            'subject' => $subject,
        ]);

        return $result;
    }

    /**
     * 同步订单状态（主动查单兜底）：
     * 1. 校验订单归属；
     * 2. 已支付订单直接返回（幂等）；
     * 3. 缓存防抖：同一订单号 5 秒内只向支付宝发起一次真实查单；
     * 4. 查单成功后事务内 lockForUpdate 锁定订单，再次检查状态，确保只入账一次；
     * 5. 更新订单状态为 Paid，调用 autoDeliverVirtualOrder 和 settleCommission；
     * 6. 发货/分账异常不阻断状态更新，记录日志后继续。
     *
     * @throws RuntimeException 订单不存在
     */
    public function syncOrderStatus(int $orderId, int $userId): Order
    {
        /** @var Order|null $order */
        $order = Order::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();

        if (! $order) {
            throw new RuntimeException('订单不存在');
        }

        // 幂等：已支付订单直接返回
        if (in_array($order->status, [OrderStatus::Paid, OrderStatus::PendingShipment, OrderStatus::Completed], true)) {
            return $order;
        }

        // 仅待支付订单需要查单
        if (! $order->status->canPay()) {
            return $order;
        }

        // 缓存防抖：5 秒内同一订单号只查一次支付宝
        $cacheKey = "payment_query:{$order->order_no}";
        $queryResult = Cache::remember($cacheKey, 5, function () use ($order): array {
            return $this->gateway->queryPayment($order->order_no);
        });

        if (! $queryResult['paid']) {
            return $order;
        }

        // 事务内 lockForUpdate 确保并发安全
        return DB::transaction(function () use ($order, $queryResult): Order {
            /** @var Order|null $locked */
            $locked = Order::query()
                ->where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return $order;
            }

            // 双重检查：可能已被异步回调处理
            if (in_array($locked->status, [OrderStatus::Paid, OrderStatus::PendingShipment, OrderStatus::Completed], true)) {
                return $locked;
            }

            // 更新订单状态为已支付
            $locked->status = OrderStatus::Paid;
            $locked->paid_at = now();
            $locked->save();

            Log::info('order synced to paid via queryPayment', [
                'order_no' => $locked->order_no,
                'trade_status' => $queryResult['trade_status'] ?? '',
            ]);

            // 发货和分账：异常不阻断状态更新
            try {
                $product = Product::withTrashed()->find($locked->product_id);
                if ($product !== null) {
                    $this->autoDeliverVirtualOrder($locked, $product);
                }
            } catch (Throwable $e) {
                Log::error('autoDeliverVirtualOrder failed in syncOrderStatus', [
                    'order_no' => $locked->order_no,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $this->settleCommission($locked);
            } catch (Throwable $e) {
                Log::error('settleCommission failed in syncOrderStatus', [
                    'order_no' => $locked->order_no,
                    'error' => $e->getMessage(),
                ]);
            }

            return $locked;
        });
    }

    /**
     * 虚拟商品自动发货（幂等）：
     * 1. 缓存锁防并发重复触发（payOrder 行锁之外的第二道防线，兼容支付回调等多入口）；
     * 2. WalletContract::deposit 以 out_trade_no 唯一约束兜底，天然幂等，绝不重复加额度；
     * 3. 仅当绑定技能且赠送额度 > 0 时入账。
     */
    /** 支付宝异步回调更新订单状态时调用 */
    private function autoDeliverVirtualOrder(Order $order, Product $product): void
    {
        $lock = Cache::lock("virtual_deliver:{$order->id}", 30);

        if (! $lock->get()) {
            // 并发触发：另一进程正在发货，订单状态由其推进，此处直接返回
            return;
        }

        try {
            if ($product->skill_id !== null && $product->quota > 0) {
                $result = $this->wallet->deposit(
                    $order->user,
                    (int) $product->quota,
                    'virtual_deliver_'.$order->id,
                );
                Log::info('virtual order auto delivered', [
                    'order_no' => $order->order_no,
                    'skill_id' => $product->skill_id,
                    'quota' => $product->quota,
                    'deposit_status' => $result['status'] ?? 'unknown',
                ]);
            }
        } finally {
            $lock->release();
        }
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
    /** 支付宝异步回调更新订单状态时调用 */
    private function settleCommission(Order $order): void
    {
        // 幂等：同一订单的分账流水已存在则跳过（payOrder 已对订单行加悲观锁，此处双保险）
        $settled = $this->ledger->hasBizEntries('order_commission', $order->id);
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

        $this->wallet->record('order_commission', $order->id, $splits);
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
