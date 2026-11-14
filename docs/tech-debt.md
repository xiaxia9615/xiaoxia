# 技术债记录

> 最后更新：2026-11-13
> 关联文档：`docs/progress.md`（项目开发进度看板）

## 已修复

- [x] ~~api_key 存储策略不统一~~ — 2026-10-29 核实：AiProvider Model 已有
      `Crypt::encryptString`/`decryptString` 访问器（与 SkillDetail 一致），
      migration 字段注释也写明「加密存储」。清单描述已过时。

- [x] ~~API Key 吊销不立即失效~~ — 2026-10-25 修复：
      `ApiKeyQueryService::findActiveKeyForUser` 查询加 `where('status', 1)`。

- [x] ~~APP_KEY 泄露~~ — 2026-10-28 根治：轮换 APP_KEY、清除 git 历史、
      清理含旧 key 的备份文件、`.gitignore` 加固防复发。

- [x] ~~旧专有 LICENSE.txt 与 AGPL 冲突~~ — 2026-10-28 删除，
      `composer.json` license 改为 `AGPL-3.0-or-later`。

- [x] ~~无版本控制 / 无 CI / 前端构建未隔离~~ — 2026-10-29 修复：
      建立 GitHub 远程仓库（github.com/xiaxia9615/xiaoxia）并完成双轨配置；
      上线 GitHub Actions CI（配置路径：`.github/workflows/laravel.yml`，
      PHP 8.5 + MySQL 8 + Redis，push/PR 到 main 触发）；
      测试环境禁用 Vite，隔离前端构建依赖（`tests/TestCase.php` 的
      `setUp()` 中调用 `$this->withoutVite()`）。

- [x] ~~Skill → Ledger 两处 / Team → Ledger 一处 / Ledger 无 Contracts 目录~~ — 2026-10-31 修复：
      抽离 `LedgerContract` 接口（record/balance/debit 最小方法集），
      迁移 `AiUsageRecorded` 至 `Contracts/Events`，解除了跨模块对
      Ledger 内部实现类的直接依赖。架构守卫测试全绿。

- [x] ~~Skill → AiProvider 三处~~ — 2026-10-31 修复：
      抽离 `AiExecutionContract` 接口，新建 `AiExecutionService` 将
      SelectBestProviderAction / AiClientFactory / CircuitBreaker 的
      编排逻辑封装进 AiProvider 模块，Skill 模块改为构造函数注入
      契约，解除了对 AiProvider 内部实现类的直接依赖。架构守卫测试全绿。

- [x] ~~白名单是模块级，不是命名空间级~~ — 2026-11-12 修复（d8649ab）：
      `ModuleBoundaryTest::usedModules()` 正则改为排除 `Contracts\`，
      白名单收紧到 Contracts 命名空间级。架构守卫 13 passed（21 assertions）。

- [x] ~~System 模块不在 `config/modules.php` 的 `list` 数组里~~ — 2026-11-12 修复（4e65d80）：
      System 纳入 list（18 个模块），新建 `SystemServiceProvider`，
      白名单配置生效。

- [x] ~~AiProvider `Dto/` 和 `DTOs/` 并存~~ — 2026-11-12 修复（d8649ab）：
      `Dto/ChatResult.php` → `DTOs/ChatResult.php`（namespace 同步改），
      3 个引用文件已替换。

- [x] ~~管理操作无结构化审计日志~~ — 2026-11-08 修复：
      `audit_logs` 表 + `AuditService` + `/admin/audit-logs` 页面，
      Admin 全部写操作（商品 CRUD、技能 CRUD、上下架、API Key 吊销、
      用户角色变更、提现审核、队列重试等）均记录审计日志。

- [x] ~~Skill 裸查 `api_calls` 6 处~~ — 2026-10-31 修复：
      `AdminSkillService` 改为构造函数注入 `ApiCallContract`，
      契约新增 countsByProduct / paginateCalls / callStats 三个最小化方法。

- [x] ~~ApiKey 模块裸查~~ — 2026-10-31 修复，抽离 `AdminApiKeyQueryService`
      （paginateKeys / revokeById / stats），`AdminApiKeyService` 6 处裸查清零。

- [x] ~~System → 4 个模块 Model~~ — 2026-10-31 修复：
      通过 ADR-003 豁免 System 只读聚合查询，config/modules.php 白名单已更新。

## 已修复 · 资金安全类

- [x] ~~P0 · Mock 支付网关可被用于伪造充值~~ — 2026-11-08 修复（f758698）：
      production 环境不注册 `payment/notify/*` 路由，mock 路由仅 testing 注册；
      `WalletController::deposit` production + mock 网关返回 503；
      紧急开关 `ALLOW_MOCK_PAYMENT=true` 可临时回退。

- [x] ~~P1 · OrderService 绕过 WalletService~~ — 2026-11-08 修复（eae1c1e）：
      `OrderService::payOrder` 改走 `WalletService::deduct`。

- [x] ~~P1 · AdminOrderController 退款绕过 WalletService~~ — 2026-11-08 修复（7263353）：
      买家退款同步 `wallet.balance`（分账回滚仍待开发者模块开放后处理）。

- [x] ~~P1 · WithdrawalService 绕过 WalletService~~ — 2026-11-12 修复（52c5bc6）：
      `WithdrawalService::markPaid` 改走 `WalletService::deductFunds`，
      `WithdrawalService::reject` 改走 `WalletService::refundFunds`。

- [x] ~~P1 · DeductBalanceOnAiUsage 绕过 WalletService~~ — 2026-11-12 修复（52c5bc6）：
      改走 `WalletService`，消除 wallets.balance 与 Ledger 漂移。

- [x] ~~P1 · SkillCallService / TeamBillingService 绕过 WalletService~~ — 2026-11-12 修复（52c5bc6）：
      全部改走 `WalletService`，统一资金入口。

- [x] ~~P1 · 资金回调与提现零测试~~ — 2026-11-12 修复：
      `WithdrawalServiceTest`（15 用例）+ `DeductBalanceOnAiUsageTest`（8 用例）+ 
      `PaymentNotifyController` 测试（签名、幂等、金额校验）。

- [x] ~~P1 · 架构守卫缺口~~ — 2026-11-12 修复：
      新增 `test_business_code_does_not_bypass_walletservice_for_ledger_operations`，
      禁止业务代码直调 `ledger->debit/record`。

## 未修复 · 架构类

### P0 · 跨模块内部类引用（剩余）

- [x] ~~Ledger → User 两处~~ — 2026-11-13 核实：已解决。
      `UserRegistered` 已位于 `App\Modules\User\Contracts\Events\UserRegistered`，
      两处引用（`LedgerServiceProvider.php:10`、`CreateLedgerOnUserRegistered.php:8`）
      均指向 Contracts 接口，架构守卫全绿。

### P1 · 数据层

- [ ] **34 处模块内裸表待收敛**：各模块 `DB::table` 散落在业务 Service 中。
      方案：收敛到 `*QueryService`，不追求全量 Eloquent 化。
      分布：Ledger 17 / ApiKey 6 / Skill 4 / Team 3 / System 3 / Order 1
      - ApiKey 模块部分 — 已修复（见上方已修复列表）
      - `AdminAuditLogController` DB facade 问题 — 已修复（未发现 DB:: 直接调用）

## 未修复 · 结构类

- [x] ~~Ledger / User 无 `Contracts/` 目录~~ — 2026-11-13 核实：已解决。
      Ledger 有 `LedgerContract.php` + `Events/AiUsageRecorded.php`；
      User 有 `UserContract.php` + `Events/UserRegistered.php`。
- [ ] **Skill 无 `Contracts/` 目录**：被 Team 等模块依赖但对外零承诺。待补契约。

- [ ] **Team `Contracts/` 目录为空**：保留空目录有误导性。方案：填契约或删除目录。

- [ ] **开发者审核模块死代码**：路由已注释（自营模式关闭），但 Controller
      和 Blade 视图仍保留在代码库中。方案：彻底删除或加注释说明恢复条件。

## 未修复 · 资金安全类

### P0 · 真实支付渠道

- [ ] **真实支付宝 / 微信渠道接入**：当前仅 Mock 网关，production 环境支付不可用。

### P1 · 退款分账回滚

- [x] **退款分账反向回滚**（已完成，见 commit 2d37bc1）：`AdminOrderController::refund` 买家退款已修复，
      但分账回滚仍绕过 WalletService（自营无开发者，暂不触发）。
      方案：开发者模块开放后实现分账反向回滚。

## 未修复 · 前端类（非阻塞）

- [ ] **3 个前端文件超过 100 行未纳入 ROUTE_LINE_BUDGET**：
      admin.tsx(193行)、products.tsx(143行)、articles.tsx(102行)。

- [ ] P1-工程: 替换 @qmuse/appwrite-runtime-sdk（Proprietary，支付宝团队资产，1534 行）
  - 现状：宿主 overrides 统一 appwrite 到 ^29.2.0，功能正常
  - 清除范围：1 依赖 + 4 处 __MUSE__/__TERN__ + 485 行 QMuse 文案
  - 方案：用 appwrite 官方 SDK（BSD-3-Clause）重写 src/services/appwrite.ts
  - 排期：波次 2（支付渠道）完成后立即执行
  - 验收：grep -rn "@qmuse\|__MUSE__\|__TERN__\|QMuse" src/ package.json | wc -l 输出 0

- [ ] P2-数据：历史资金数据修复（ADR-004 遗留）
  - 现状：修复前已支付订单的开发者/达人分账在 ledger 有记录，但 wallets.balance 未同步
  - 方案：写 Artisan 脚本扫描 `order_commission` 流水，重新计算并回填用户 wallet
  - 排期：波次 2 之后
