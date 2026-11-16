# 变更日志

本项目所有重要变更记录于此文件。
格式参考 [Keep a Changelog](https://keepachangelog.com/)。

---

## 2026-11-16

### Features
- **Admin 后台彻底重构**：拆分 14 个模块路由，抽离统一 `AdminPageLayout`、`DataTable`、`Pagination`、`StatusBadge` 共享组件；`AdminShell` 升级为 5 组折叠菜单（概览/交易/资产/AI/系统）+ 顶部面包屑（`84ecce2`）
- **仪表盘极客暗黑风**：纯 SVG 折线图、动态数字滚动、CRT 扫描线效果（`4361199` 基线）
- **核心模块真实数据对接**（零 Mock）：财务中心（充值流水/提现审批双 Tab、金额毫转元、驳回原因表单化）、用户列表、AI 供应商（编辑弹窗）、技能（上下架切换）、API Keys（吊销确认）、调用流水（真实消耗与耗时）（`84ecce2` `1e6b80b`）
- **后端 JSON API 补齐**：ai-providers / skills / api-keys / api-calls 四模块 7 条新路由，全部挂 `v1/admin` + `auth:sanctum`（`1e6b80b`）
- **双态商品与自动发货闭环**：`products` 表新增 `nature`（physical/virtual）、`quota`、`skill_id`；支付成功后自动分流——实物进入待发货，虚拟商品秒级自动发放额度并完成订单（`2b1ec65`）

### Security
- **后端 API 全面脱敏**：AI 供应商与 API Keys 输出 `masked_key`（sk-****后4位 / key_prefix+****），绝不返回明文密钥（`1e6b80b`）
- **密钥防覆盖**：apiUpdate 的 api_key 留空时保留原值，绝不盲目覆盖（`1e6b80b`）
- **前端路由守卫体系**：新增 `requireAuth`（13 个敏感路由 beforeLoad 强拦截）与 `requireAdmin`（/admin 全部子路由 + /source-export，登录 + is_admin 双重校验），彻底杜绝未登录看到后台骨架（`b93b5b2` `d16749c`）
- **AuthProvider 加固**：启动校验期间全屏 Loading 防骨架闪现 + 401 事件强制跳转登录页（携带 redirect 回跳参数）（`b93b5b2`）

### Refactor
- **CI Larastan 修复**：5 文件 PHPDoc 与闭包 use 修复，零业务逻辑改动，phpstan 0 errors（`a37c713`）
- **变异测试验证与清理**：infection 完成资金链路（Wallet/Order）变异测试后清理现场，不留 CI 负担（`f00d38d`）
- **移除历史遗留**：清理 QMuse 依赖及外链图床

### Quality
- 后端测试 224 → **233 passed / 4 skipped（790 断言）** 全绿
- 前端 `npm run typecheck` 0 errors + `npm run build` 通过
- GitHub Actions CI 流水线彻底转绿，连续多次通过

---

## 2026-11-13

### Features
- 完成 Admin 后台全量功能：技能配置中心 CRUD、API 调用流水审计、队列监控面板（`83e851f`）

### Documentation
- 同步技术债状态：所有 P0/P1 资金安全项已修复，架构债已更新（`f9bbba7`）
- 同步项目进度：224 测试、18 模块、Admin 功能完成（`cc63fcc`）
- 更新自动体检日志 Stage-1 结果（`523a5e9`）

---

## 2026-11-12

### Features
- 完成最后 4 个后端模块（Article、Browse、Comment、Message），200 测试全绿（`3eccd8b`）
- 完成 Admin 仪表盘真实数据绑定，全量测试通过（`31d7ea3`）

### Refactoring
- 解决 P0-2 技术债：统一测试充值走 WalletService，224 测试全绿（`52c5bc6`）
- 解决 P2 结构性技术债：强制命名空间级边界（`d8649ab`）
- 提交 P2 DTO 迁移和 System Provider 缺失文件（`4e65d80`）

### Chores
- 包含前端架构修复脚本并更新模块依赖（`5a7c932`）

---

## 2026-11-10

### Features
- 实现购物车和地址模块，修复 auth guard，186 测试通过（`4ef0aed`）
- 完成 40 个缺失 API 端点骨架，同步路由和文档，修复 404 回退（`4795862`）
- 添加 React SPA 入口（`3a571a7`）

### Bug Fixes
- 修复存储权限，更新 SPA 示例测试，186 测试全绿（`096a30b`）
- 修复前端所有 TypeScript 错误和依赖（`daa05ce`）
- 加固支付网关并配置 PHPUnit Mock 测试（`667c2c2`）

### Build
- 解决所有 TS 错误，修复依赖并构建生产资源（`4963757`）
- 同步前端构建配置、SPA 路由和 tsconfig（`dc42e3c`）

### Quality
- 100% 测试覆盖率，0 错误，准备 v1.0.0 发布（`13ebda0`）

---

## 2026-11-09

### Documentation
- 生成 API 文档（Blade 视图、静态资源、Postman 集合、OpenAPI 规范）（`3a92bde`）

### Quality
- 解决所有剩余 PHPStan 错误（0 errors）（`ced75dc`）
- 为多个模型添加 @property PHPDoc 注解（`9bc589f` `5be04a3` `1da929b` `98167d3` `d0880ff`）
- 收窄关联返回类型（AiModel::provider, ApiCall::user）（`01bf00c`）
- 标注 AiProvider api_key 为 string|null（`e947769`）

### Bug Fixes
- 修复 AI 模块空 api_key 防护（`47ed96b`）
