# 技术债记录

> 最后更新：2026-11-08
> 关联文档：`docs/progress.md`（D1–D4 改造进度）

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
      建立 GitHub 远程仓库（github.com/xiaxia9615/xiaoxia）并完成双轨开源配置；
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

## 未修复 · 架构类（D1–D4 计划内）

### P0 · 白名单粒度（D1 修）

- [ ] **白名单是模块级，不是命名空间级**：`config/modules.php` 填
      `'Skill' => ['ApiKey']` 同时放行 `ApiKey\Contracts\*`（合规）和
      `ApiKey\Services\*`（违规）。导致「12 项测试全绿」与「10 处违规共存」。
      方案：改三个测试方法判断逻辑 + 白名单改为 `模块\Contracts` 格式。
      详见 `docs/progress.md` D1。

### P0 · 跨模块内部类引用（T2 逐个修）

- [x] **Skill → AiProvider 三处**：`SelectBestProviderAction`、
      `AiClientFactory`、`CircuitBreaker`（`ExecuteSkillAction.php:7-9`）。
      方案：AiProvider 补 `AiExecutionContract`，把三者编排封装进 AiProvider，2026-10-31 修复：抽离 AiExecutionContract 接口。

- [ ] **Skill → Ledger 两处**：`LedgerService`（`SkillCallService.php:10`）、
      `AiUsageRecorded`（`ExecuteSkillAction.php:10`）。
      方案：Ledger 建 `Contracts/LedgerContract` + `Contracts/AiUsageRecorded`。

- [ ] **Team → Ledger 一处**：`LedgerService`（`TeamBillingService.php:7`）。
      与上一条同批修。

- [ ] **Ledger → User 两处**：`UserRegistered`（`LedgerServiceProvider.php:10`、
      `CreateLedgerOnUserRegistered.php:8`）。方案：User 建
      `Contracts/UserRegistered`，事件迁入。

- [x] **System → 4 个模块 Model**：`ApiCall`、`ApiKey`、`Product`、`SkillDetail`
      （`DashboardService.php:7-10`）。方案二选一：System 纳入 list 并建
      Reader 契约，或写 ADR 豁免「视图聚合层」并纳入测试扫描。
      - 2026-10-31 修复：通过 ADR-003 豁免 System 只读聚合查询，config/modules.php 白名单已更新。

### P1 · 数据层

- [x] **Skill 裸查 `api_calls` 6 处**：`AdminSkillService.php` 直接
      `DB::table('api_calls')`，绕过已存在的 `ApiCallContract`。
      方案：改注入 `ApiCallContract`。
      - 2026-10-31 修复：`AdminSkillService` 改为构造函数注入 `ApiCallContract`，
        契约新增 countsByProduct / paginateCalls / callStats 三个最小化方法。

- [ ] **30 处模块内裸表待收敛**：各模块 `DB::table` 散落在业务 Service 中。
      方案：收敛到 `*QueryService`，不追求全量 Eloquent 化。
      - ApiKey 模块部分 — 2026-10-31 修复，抽离 `AdminApiKeyQueryService`
        （paginateKeys / revokeById / stats），`AdminApiKeyService` 6 处裸查清零。

## 未修复 · 结构类

- [ ] **System 模块不在 `config/modules.php` 的 `list` 数组里**：
      当前 `in_list=NO / in_dependencies=YES`，白名单写了但测试可能扫不到，
      等于死配置。方案：纳入 list 或写 ADR 豁免。

- [ ] **Ledger / Skill / User 无 `Contracts/` 目录**：被跨模块依赖但对外零承诺。
      方案：随 T2 逐个补契约（Ledger 最优先，被 Skill 和 Team 依赖）。

- [ ] **Team `Contracts/` 目录为空**：保留空目录有误导性。方案：填契约或删除目录。

- [ ] **AiProvider `Dto/` 和 `DTOs/` 并存**：命名不统一。方案：确认无同名后合并为 `Dto/`。

## 未修复 · 安全 / 合规类

- [ ] **管理操作无结构化审计日志**：吊销 API Key 等操作只 `Log::info`，
      无 `audit_logs` 表。当前无合规压力，暂缓；有多人操作后台时再做。

## 未修复 · 资金安全类（2026-11-08 新增）

### ~~P0 · Mock 支付网关可被用于伪造充值~~ ✅ 2026-11-08 已修复

**修复状态**：2026-11-08 已修复，commit `f758698`。

实际采用的方案（与下方历史方案不同）：

- `routes/api.php`：production 环境不注册 `payment/notify/*` 任何路由（alipay/wechat），mock 路由仅 testing 注册
- `WalletController::deposit`：production + mock 网关返回 503，不创建垃圾 pending 订单
- 紧急开关：`.env` 设 `ALLOW_MOCK_PAYMENT=true` 可临时回退
- 守卫用 `config('app.env')` 而非 `env('APP_ENV')`，测试可驱动

**⚠️ 下方「修复方案（二选一）」是历史记录，已否决，请勿执行**：在 `PaymentGatewayFactory::make()` 加守卫会在 `.env` 为 `APP_ENV=production` 时引发全线 500（所有 `app(PaymentGatewayInterface::class)` 调用抛异常）。

**问题**：`config/payment.php` 默认 `PAYMENT_GATEWAY=mock`，当前 `.env` 未配置任何真实渠道。`MockPaymentGateway::verifyNotify` 无条件返回 `verified=true`。

**攻击路径**：
1. 用户正常创建充值订单 `POST /api/v1/wallet/deposit`，获得 `order_no`
2. 用户直接 POST `/api/v1/payment/notify/alipay`，body 带自己的 `order_no` 和 `amount_in_cents`
3. 回调进入 `PaymentNotifyController`，Mock 网关验签通过
4. 订单置 `paid`，`WalletService::deposit` 到账

用户未付一分钱即可充值。

### P1 · 资金变动绕过 WalletService 导致 wallet 缓存漂移

`wallets.balance` 是 Ledger 的视图缓存，`WalletService` 是唯一同步入口。以下调用点直接操作 Ledger，不同步 `wallet.balance`：
- [x] `OrderService.php:101` — 2026-11-08 已修复，改走 `WalletService::deduct`
- [x] `AdminOrderController.php:118` — 2026-11-08 买家退款已修复，分账回滚仍绕过（自营无开发者，暂不触发）
- [ ] `WithdrawalService.php:76` — 仍绕过
- [ ] `DeductBalanceOnAiUsage.php:45` — 仍绕过

**后果**：用户界面余额与 Ledger 实际余额漂移。支付后退款，用户看到『退款成功但余额没回来』。

> 2026-11-08 更新：支付链路已修复并测试覆盖。退款、提现、AI 用量三处
> 仍在绕过 WalletService，会产生新的漂移。退款修复需等支付稳定后单独
> 排期，因为涉及分账反向回滚，比支付复杂。

### P1 · 资金回调与提现零测试

- `PaymentNotifyController` 无测试：签名验证、幂等、金额校验均无回归保护
- `WithdrawalService` 无测试：apply / approve / reject / mark-paid 四个状态 + 原子退款无保护

### P1 · 架构守卫缺口

无守卫阻止绕过 `WalletService` 直接调 `ledger->debit/record` 改 user balance。
