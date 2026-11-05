<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Controllers;

use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

final class WalletController
{
    public function __construct(
        private readonly WalletService $service,
    ) {}

    /**
     * 查询钱包余额
     *
     * @group 钱包管理
     *
     * @authenticated
     *
     * @response {
     *  "code": 0,
     *  "data": {
     *    "balance": 10000,
     *    "frozen_balance": 0
     *  }
     * }
     */
    public function show(Request $request): JsonResponse
    {
        $balance = $this->service->getBalance($request->user());

        return response()->json([
            'code' => 0,
            'data' => [
                'balance' => $balance['balance'],
                'frozen_balance' => $balance['frozen_balance'],
            ],
        ]);
    }

    /**
     * 冻结资金
     *
     * @group 钱包管理
     *
     * @authenticated
     *
     * @bodyParam amount int required 冻结金额（分） Example: 3000
     *
     * @response 200 {
     *  "code": 0,
     *  "data": {
     *    "balance": 7000,
     *    "frozen_balance": 3000
     *  }
     * }
     * @response 402 {
     *  "code": 1,
     *  "message": "余额不足"
     * }
     */
    public function freeze(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        try {
            $this->service->freezeFunds($request->user(), (int) $data['amount']);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 402);
        }

        $balance = $this->service->getBalance($request->user());

        return response()->json([
            'code' => 0,
            'data' => [
                'balance' => $balance['balance'],
                'frozen_balance' => $balance['frozen_balance'],
            ],
        ]);
    }

    /**
     * 模拟充值
     *
     * @group 钱包管理
     *
     * @authenticated
     *
     * @bodyParam amount int required 充值金额（分） Example: 5000
     *
     * @response 200 {
     *  "code": 0,
     *  "data": {
     *    "balance": 5000,
     *    "frozen_balance": 0
     *  }
     * }
     */
    public function deposit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1',
        ]);

        try {
            $this->service->deposit($request->user(), (int) $data['amount'], Str::uuid()->toString());
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 422);
        }

        $balance = $this->service->getBalance($request->user());

        return response()->json([
            'code' => 0,
            'data' => [
                'balance' => $balance['balance'],
                'frozen_balance' => $balance['frozen_balance'],
            ],
        ]);
    }
}
