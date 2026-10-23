<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Http\Controllers;

use App\Modules\Ledger\Services\LedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LedgerController
{
    public function __construct(private readonly LedgerService $ledger) {}

    public function balance(Request $request): JsonResponse
    {
        $balance = $this->ledger->balance('user', $request->user()->id, 'balance');

        return response()->json(['code' => 0, 'data' => ['balance' => $balance]]);
    }

    public function entries(Request $request): JsonResponse
    {
        $items = $this->ledger->entriesByUser((int) $request->user()->id);
        if ($items === null) {
            return response()->json(['code' => 0, 'data' => ['items' => [], 'total' => 0]]);
        }

        return response()->json(['code' => 0, 'data' => $items]);
    }
}
