<?php

declare(strict_types=1);

namespace App\Modules\Skill\Http\Controllers;

use App\Modules\Skill\Services\SkillCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        try {
            $result = $this->service->call($request->user(), $id, $input);
            return response()->json(['code' => 0, 'message' => 'success', 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        }
    }
}
