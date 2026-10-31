# ADR-003: System 模块聚合查询豁免

- 状态：已接受
- 日期：2026-10-31
- 关联：`docs/tech-debt.md` P0 债务

## 背景
`System` 模块下的 `DashboardService` 为了渲染后台仪表盘，需要聚合查询其他 4 个模块（`ApiCall`、`ApiKey`、`Product`、`SkillDetail`）的表数据。如果为此建立 4 个跨模块的接口契约，会造成严重的过度设计。

## 决策
将 `System` 模块定义为“只读视图聚合层”。允许其直接跨模块查询数据库（Read-Only），但不允许跨模块写入或实例化其他模块的 Model 类。

## 后果
- `config/modules.php` 中 `System` 模块允许声明跨模块表依赖。
- 架构守卫测试 `tests/Architecture/` 继续严格执行，不再新增针对 System 模块的强拦截。
