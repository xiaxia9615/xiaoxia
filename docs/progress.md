# 项目开发进度看板

> 最后更新：2026-11-13 | 状态：Admin 后台全量功能就绪，资金链路已加固

## 📊 核心数据

| 维度 | 状态 | 说明 |
|---|---|---|
| 测试基线 | **224 passed / 4 skipped / 748 断言** | 全绿，4 skip 为开发者模块休眠 |
| CI/CD | 已上线 | GitHub Actions（PHP 8.5 + MySQL 8 + Redis） |
| 架构守卫 | 13 项全绿（21 assertions） | 模块边界 + 防裸查 + 防绕过 WalletService |
| 静态分析 | Larastan 0 errors + Pint 0 issues | 已接入 |
| 模块数 | 18 个 | config/modules.php list 全量注册 |
| PHP 文件 | 184 个 | app/ 目录 |
| 迁移文件 | 19 个 | database/migrations/ |
| 路由总数 | 140 条 | 含 Admin + API + SPA 兜底 |
| Git 远程 | origin → Gitee + GitHub 双推 | 私有化仓库 |

## 🟢 已完成

### 工程基建
- CI/CD、模块化单体（18 模块）、Contracts 隔离、AGPL-3.0
- Larastan 静态分析、Pint 格式化
- 模块边界白名单收紧到 Contracts 命名空间级（d8649ab）
- AiProvider Dto/DTOs/ 合并统一（d8649ab）
- System 模块纳入 config/modules.php list + SystemServiceProvider（4e65d80）

### 核心 AI 与账本
- 多 AI 供应商智能路由（17 供应商、14 模型、熔断）
- 复式记账 Ledger（BigInt 毫精度、悲观锁、防重复扣费）
- Wallet 资金缓存层

### 交易闭环
- 商品管理（Admin CRUD、强制下架、审计日志）
- 订单（创建、支付、取消、退款、分账）
- 单级达人分销（绑定、自动分佣、幂等）

### 支付与提现
- 支付网关抽象（`PaymentGatewayInterface`）+ Mock 实现
- 充值订单创建、回调处理、幂等与金额校验
- 提现审批流（apply / approve / reject / mark-paid）
- 财务中心（充值流水、提现审批、导出）

### Admin 后台功能（2026-11-13 完善）
- **技能配置中心**：创建/编辑/上下架，双表事务（Product + SkillDetail），价格元转毫，AI Provider 绑定，System Prompt 字段，审计日志全覆盖
- **调用流水与账本核对**：多维筛选（用户/技能/时间段）+ ledger_entries 关联展示
- **队列监控面板**：jobs/failed_jobs 读取 + 失败重试（Artisan::call）+ 审计日志
- **审计日志列表**：支持按 action/operator_id/target_type 筛选
- **侧边栏导航**：14 项菜单，routeIs 高亮自动覆盖

### 资金安全加固（P0/P1 已修复）
- [x] Mock 网关禁止在非 local/testing 环境生效（f758698）
- [x] `OrderService::payOrder` 改走 `WalletService::deduct`（eae1c1e）
- [x] `AdminOrderController::refund` 买家退款同步 `wallet.balance`（7263353）
- [x] `WithdrawalService` 改走 `WalletService::deductFunds/refundFunds`（52c5bc6）
- [x] `DeductBalanceOnAiUsage` 改走 `WalletService`（52c5bc6）
- [x] `SkillCallService` / `TeamBillingService` 全部改走 `WalletService`（52c5bc6）

### 测试补强
- [x] `PaymentNotifyController` 测试（签名、幂等、金额校验）
- [x] `WithdrawalServiceTest`（15 用例）
- [x] `DeductBalanceOnAiUsageTest`（8 用例）

### 审计与运维
- 管理操作审计日志（`audit_logs` 表 + `/admin/audit-logs` 页面）
- Queue Worker 健康检查支持 sync 模式
- 系统健康检查（MySQL/Redis/Nginx/PHP-FPM/Worker）
- 队列监控 + 失败重试

### 架构审计 + 技术债清理
- P0-2：WithdrawalService / DeductBalanceOnAiUsage / OrderService / SkillCallService / TeamBillingService 全部改走 WalletService，消除 wallets.balance 与 Ledger 漂移
- P2 结构性技术债：System 模块纳入 list、AiProvider Dto/→DTOs/ 合并、白名单收紧到 Contracts 命名空间级

## 🚧 进行中 / 待办

### P0 · 资金安全
- [ ] 真实支付宝 / 微信渠道接入
- [ ] 退款分账回滚（开发者模块开放后）

### P1 · 测试补强
- [ ] `WithdrawalService` 状态流转测试（已有 15 用例，需补充边界场景）

### P2 · 架构优化
- [ ] 30 处模块内裸表收敛到 `*QueryService`
- [ ] `AdminAuditLogController` 改走 Service 层（当前直接用 DB facade）
- [ ] 开发者审核模块死代码清理（路由已注释，Controller/View 仍在）

### 前端提示项（非阻塞）
- [ ] admin.tsx(193行)、products.tsx(143行)、articles.tsx(102行) 超过 100 行未纳入 ROUTE_LINE_BUDGET

## 📋 未来规划
- 真实支付渠道接入
- 退款分账回滚
- 开发者模块恢复（考取资质后）

## 📌 Git 提交历史（近期）

| Commit | 说明 |
|--------|------|
| `83e851f` | feat(admin): complete admin skill CRUD, api-calls audit, and queue monitor |
| `523a5e9` | docs: update health log with stage-1 examination results |
| `4e65d80` | chore(arch): commit missing files for P2 DTO migration and System provider |
| `d8649ab` | chore(arch): resolve P2 structural tech debt, enforce namespace-level boundary |
| `5a7c932` | chore: include frontend architecture fix script and update module dependencies |
| `52c5bc6` | refactor(wallet): resolve P0-2 technical debt, unify test top-up via WalletService |
