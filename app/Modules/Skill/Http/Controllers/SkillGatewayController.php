<?php

declare(strict_types=1);

namespace App\Modules\Skill\Http\Controllers;

use App\Modules\Skill\Actions\ExecuteSkillAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class SkillGatewayController
{
    public function __construct(
        private readonly ExecuteSkillAction $action,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $prompt = (string) $request->input('prompt', '');
        if ($prompt === '') {
            return response()->json(['code' => 1, 'message' => 'prompt 不能为空'], 400);
        }
        try {
            $result = $this->action->execute((int) $request->user()->id, $prompt);

            return response()->json(['code' => 0, 'message' => 'success', 'data' => $result]);
        } catch (RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('skill gateway failed', ['error' => $e->getMessage()]);

            return response()->json(['code' => 1, 'message' => '系统繁忙，请稍后重试'], 500);
        }
    }
}
