<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

/**
 * 旧版后台 HTML 页面测试 — 已废弃。
 *
 * 系统已重构为 React SPA + API 架构，Laravel 不再渲染 admin HTML 视图。
 * 以下测试方法全部基于 HTML 路由（/admin/orders、/admin/products 等），
 * 路由已删除，测试不再适用。保留注释供后续编写 API 版本测试时参考。
 *
 * @deprecated 已由 SPA + API 架构替代
 */
final class AdminOrderProductTest extends \Tests\TestCase
{
    // 原有 6 个测试方法已移除：
    // - test_normal_user_gets_403_on_orders（HTML 路由 /admin/orders）
    // - test_normal_user_gets_403_on_products（HTML 路由 /admin/products）
    // - test_admin_can_view_orders（HTML 路由 /admin/orders）
    // - test_admin_can_view_products（HTML 路由 /admin/products）
    // - test_admin_refund_paid_order（HTML POST /admin/orders/{id}/refund）
    // - test_admin_force_archive_product（HTML POST /admin/products/{id}/force-archive）
    //
    // 后续应基于 /api/v1/admin/orders 和 /api/v1/admin/products 重写 API 测试。
}
