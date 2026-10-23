<?php

declare(strict_types=1);

namespace App\Modules\Skill\Http\Controllers;

use App\Modules\Skill\Services\SkillCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class SkillCallController
{
    public function __construct(private readonly SkillCallService $service) {}

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $input = $request->input('input', []);
        if (is_string($input)) {
            $input = ['text' => $input];
        }
        if (! is_array($input)) {
            $input = [];
        }

        $requestId = $request->input('request_id');

        try {
            $result = $this->service->call($request->user(), $id, $input, $requestId);

            return response()->json(['code' => 0, 'message' => 'success', 'data' => $result]);
        } catch (RuntimeException $e) {
            // 业务异常：信息是给用户看的
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            // 系统异常：记日志，不泄露内部细节
            Log::error('skill call failed', [
                'product_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['code' => 1, 'message' => '系统繁忙，请稍后重试'], 500);
        }
    }
}
