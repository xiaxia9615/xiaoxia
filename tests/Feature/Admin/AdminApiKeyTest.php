<?php

declare(strict_types=1);
use Tests\TestCase;

/**
 * 旧版后台 HTML 页面测试 — 已废弃。
 *
 * 系统已重构为 React SPA + API 架构，Laravel 不再渲染 admin HTML 视图。
 * 所有测试方法基于已删除的 HTML 路由（GET/DELETE /admin/api-keys），不再适用。
 * 其中 test_revoked_key_rejects_api_call 虽调用了 /api/v1/skills/{id}/call，
 * 但前置依赖 DELETE /admin/api-keys/{id} 撤销密钥，无法单独保留。
 * 后续应基于 /api/v1/admin/api-keys 重写 API 测试。
 *
 * @deprecated 已由 SPA + API 架构替代
 */
final class AdminApiKeyTest extends TestCase
{
    // 所有 HTML 路由测试方法已移除。
}
