<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Modules\User\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * 测试辅助 Trait：生成 UID 签名认证头。
 *
 * 签名算法与 UidAuth 中间件一致：
 * HMAC-SHA256(uid + timestamp + nonce, APP_KEY)
 */
trait UidAuthTestHelper
{
    /**
     * 为指定用户生成认证请求头。
     *
     * @return array<string, string>
     */
    protected function uidAuthHeaders(User $user): array
    {
        $uid = (string) $user->id;
        $timestamp = (string) time();
        $nonce = Str::random(32);
        $sign = hash_hmac('sha256', $uid.$timestamp.$nonce, config('app.key'));

        return [
            'X-Uid' => $uid,
            'X-Sign' => $sign,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
        ];
    }

    /**
     * 用 UID 签名头发起 GET 请求。
     *
     * @return TestResponse
     */
    protected function uidGet(User $user, string $uri, array $headers = [])
    {
        $headers = array_merge($this->uidAuthHeaders($user), $headers);

        return $this->getJson($uri, $headers);
    }

    /**
     * 用 UID 签名头发起 POST 请求。
     *
     * @return TestResponse
     */
    protected function uidPost(User $user, string $uri, array $data = [], array $headers = [])
    {
        $headers = array_merge($this->uidAuthHeaders($user), $headers);

        return $this->postJson($uri, $data, $headers);
    }

    /**
     * 用 UID 签名头发起 PUT 请求。
     *
     * @return TestResponse
     */
    protected function uidPut(User $user, string $uri, array $data = [], array $headers = [])
    {
        $headers = array_merge($this->uidAuthHeaders($user), $headers);

        return $this->putJson($uri, $data, $headers);
    }

    /**
     * 用 UID 签名头发起 PATCH 请求。
     *
     * @return TestResponse
     */
    protected function uidPatch(User $user, string $uri, array $data = [], array $headers = [])
    {
        $headers = array_merge($this->uidAuthHeaders($user), $headers);

        return $this->patchJson($uri, $data, $headers);
    }

    /**
     * 用 UID 签名头发起 DELETE 请求。
     *
     * @return TestResponse
     */
    protected function uidDelete(User $user, string $uri, array $data = [], array $headers = [])
    {
        $headers = array_merge($this->uidAuthHeaders($user), $headers);

        return $this->deleteJson($uri, $data, $headers);
    }
}
