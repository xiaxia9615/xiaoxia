<?php

declare(strict_types=1);

namespace App\Modules\ApiKey\Http\Controllers;

use App\Modules\ApiKey\Services\ApiKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApiKeyController
{
    public function __construct(private readonly ApiKeyService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['code'=>0,'data'=>$this->service->listByUser($request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['name'=>'nullable|string|max:50']);
        return response()->json(['code'=>0,'data'=>$this->service->generate($request->user(), $data['name'] ?? 'default')]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $ok = $this->service->revoke($request->user(), $id);
        return response()->json(['code'=>$ok ? 0 : 1], $ok ? 200 : 404);
    }
}
