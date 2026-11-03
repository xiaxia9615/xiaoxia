<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Controllers;

use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class WalletController
{
    public function __construct(
        private readonly WalletService $service,
    ) {}

    /**
     * GET /api/v1/wallet：返回当前登录用户的 balance 和 frozen_balance（单位：分）。
     */
    public function show(Request $request): JsonResponse
    {
        $balance = $this->service->getBalance((int) $request->user()->id);

        return response()->json([
            'code' => 0,
            'data' => [
                'balance' => $balance['balance'],
                'frozen_balance' => $balance['frozen_balance'],
            ],
        ]);
    }

    /**
     * POST /api/v1/wallet/freeze：冻结资金，余额不足返回 402。
     */
    public function freeze(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1', // 金额（分）
        ]);

        try {
            $this->service->freezeFunds((int) $request->user()->id, (int) $data['amount']);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 402);
        }

        $balance = $this->service->getBalance((int) $request->user()->id);

        return response()->json([
            'code' => 0,
            'data' => [
                'balance' => $balance['balance'],
                'frozen_balance' => $balance['frozen_balance'],
            ],
        ]);
    }

    /**
     * POST /api/v1/wallet/deposit：模拟充值接口。
     */
    public function deposit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1', // 金额（分）
        ]);

        try {
            $this->service->deposit((int) $request->user()->id, (int) $data['amount']);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 422);
        }

        $balance = $this->service->getBalance((int) $request->user()->id);

        return response()->json([
            'code' => 0,
            'data' => [
                'balance' => $balance['balance'],
                'frozen_balance' => $balance['frozen_balance'],
            ],
        ]);
    }
}
