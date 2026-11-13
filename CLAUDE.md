# 项目规则（Agent 必读）

## 项目定位

嗣夏（Xiaoxia）——自营 AI 技能平台。Laravel 13 · PHP 8.5 · MySQL 8 · Redis · Nginx。模块化单体（`app/Modules/*`）。

## 资金铁律（违反即失败）

1. **金额单位是「毫」**：1 元 = 1000 毫。所有金额字段是 `bigint`，禁止 `decimal` / `float`。
2. **Ledger 是权威账本，`wallets.balance` 是视图缓存**，两者必须同步。
3. **所有资金变动必须经 `WalletService`**：`deposit` / `deduct` / `freezeFunds` / `deductFunds` / `refundFunds`。禁止在业务代码里直接调 `ledger->debit` / `ledger->record` 改 user balance。
4. **写操作必须 `DB::transaction` + `lockForUpdate`**。
5. **幂等**：外部可触发的资金接口必须用 `out_trade_no` 或业务键幂等。
6. **Mock 网关禁止在非 `local`/`testing` 环境生效**。

## 模块边界

- 跨模块依赖走 `Contracts/` 接口，禁止直接 `use` 对方 `Services/*` 或 `Models/*`。
- 架构守卫 `tests/Architecture/ModuleBoundaryTest.php` 检查，违反即失败。
- 新模块需在 `config/modules.php` 的 `list` 和 `dependencies` 注册。

## 测试

- 资金相关改动必须补测试。
- 全量：`php artisan test`
- 架构：`php artisan test tests/Architecture`

## 已知债务（不要重复踩）

- **已修复（P0/P1）**：`OrderService::payOrder` / `AdminOrderController::refund` / `WithdrawalService` / `DeductBalanceOnAiUsage` / `SkillCallService` / `TeamBillingService` 已全部统一走 `WalletService`。
- **已修复**：`PaymentNotifyController` / `WithdrawalService` 已补测试（15 用例 + 8 用例）。
- **已修复**：Mock 网关已限制为 `local`/`testing` 环境专用，生产环境不生效。
- **待办（P0）**：真实支付宝/微信渠道尚未接入，当前仅 Mock 网关。
- **待办（P0）**：Ledger → User 两处跨模块内部类引用待解耦（详见 `docs/tech-debt.md`）。
- **待办（P1）**：退款分账反向回滚仍绕过 WalletService（自营模式暂不触发）。
- **待办（P1）**：约 30 处模块内裸表待收敛到 `*QueryService`。

## 文档索引

- `docs/progress.md` — 进度看板
- `docs/tech-debt.md` — 技术债
- `docs/health-log.md` — 自动体检
- `docs/ops.md` — 运维手册
- `docs/adr/` — 架构决策
- `CHANGELOG.md` — 变更日志
