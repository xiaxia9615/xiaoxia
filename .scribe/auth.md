# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {SANCTUM_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

认证方式为 Laravel Sanctum：先调用 /api/v1/auth/login 获取 Token，再以 Bearer Token 方式携带在 Authorization 头中调用受保护接口。
