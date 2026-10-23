<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Controllers;

use App\Modules\User\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class AuthController
{
    public function __construct(private readonly AuthService $auth) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:50', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:6']);
        try {
            return response()->json(['code' => 0, 'data' => $this->auth->register($data['name'], $data['email'], $data['password'])]);
        } catch (\RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Auth Exception: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'code' => 500,
                'message' => '系统繁忙，请稍后再试',
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        try {
            return response()->json(['code' => 0, 'data' => $this->auth->login($data['email'], $data['password'])]);
        } catch (\RuntimeException $e) {
            return response()->json(['code' => 1, 'message' => $e->getMessage()], 401);
        } catch (\Throwable $e) {
            Log::error('Auth Exception: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'code' => 500,
                'message' => '系统繁忙，请稍后再试',
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['code' => 0, 'message' => 'ok']);
    }

    public function me(Request $request): JsonResponse
    {
        $u = $request->user();

        return response()->json(['code' => 0, 'data' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]]);
    }
}
