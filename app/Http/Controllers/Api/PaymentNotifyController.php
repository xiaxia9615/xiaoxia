<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Modules\Payment\Contracts\PaymentGatewayInterface;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class PaymentNotifyController
{
    public function wechat(Request $request): JsonResponse
    {
        return $this->handleNotify($request->all(), 'SUCCESS');
    }

    public function alipay(Request $request): JsonResponse
    {
        return $this->handleNotify($request->all(), 'success');
    }

    public function mock(Request $request): JsonResponse
    {
        return $this->handleNotify($request->all(), 'SUCCESS');
    }

    private function handleNotify(array $payload, string $successResponse): JsonResponse
    {
        $gateway = app(PaymentGatewayInterface::class);
        $result = $gateway->verifyNotify($payload);

        if (! $result['verified']) {
            return response()->json(['code' => 'FAIL'], 200);
        }

        $orderNo = $result['order_no'];

        try {
            DB::transaction(function () use ($orderNo, $result) {
                $order = PaymentOrder::query()
                    ->where('order_no', $orderNo)
                    ->lockForUpdate()
                    ->first();

                if ($order === null) {
                    throw new RuntimeException('回调订单不存在: '.$orderNo);
                }

                if ($order->status === 'paid') {
                    return; // 幂等，已处理
                }

                $amount = (int) $result['amount_in_cents'];
                if ($amount !== (int) $order->amount_in_cents) {
                    Log::warning('payment amount mismatch', [
                        'order_no' => $orderNo,
                        'expected' => $order->amount_in_cents,
                        'actual' => $amount,
                    ]);
                    throw new RuntimeException('回调金额与订单金额不一致');
                }

                $order->status = 'paid';
                $order->paid_at = now();
                $order->save();

                $user = User::query()->findOrFail($order->user_id);
                $walletService = app(WalletService::class);
                $walletService->deposit($user, $amount, $orderNo);
            });
        } catch (RuntimeException $e) {
            Log::warning('payment notify failed', [
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['code' => 'FAIL'], 200);
        }

        return response()->json(['code' => $successResponse], 200);
    }
}
