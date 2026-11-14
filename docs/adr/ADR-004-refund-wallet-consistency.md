# ADR-004: 退款链路钱包-账本一致性

- **状态**：Accepted
- **日期**：2026-11-14

## 背景

退款已做买家退款 + 开发者 ledger 扣回，但存在以下问题：

1. `wallets.balance` 与 `ledger_entries` 不一致（开发者分账后 wallet 一直为 0）
2. 达人 10% 佣金退款时是否回滚未知
3. `LedgerService::record()` 完全不碰 `wallets` 表，仅写 `ledger_entries`

## 决策

### 1. wallets.balance 定位：缓存（必须实时一致）

`wallets.balance` 是 ledger 的缓存层，必须与 `ledger_entries` 借贷净额保持一致。

### 2. 同步范围：所有 WalletService::record 调用点

在 `WalletService::record()` 内统一同步，而非逐调用点处理。所有绕过 WalletService 直接调 LedgerService::record 的位置已收敛。

### 3. 达人 10% 回滚

退款时达人 10% 佣金单独走 `order_refund_commission` biz_type 回滚，与 `order_refund` 分开记录，便于审计。

### 4. 已提现场景：拒绝退款（选 B）

达人佣金已提现导致余额不足时，拒绝退款并提示人工处理。不允许负余额。

### 5. 幂等键

现有 `biz_type='order_refund'` + `biz_id=order_id` 去重机制保留。达人回滚 `order_refund_commission` 同理。订单状态 `canRefund()` 作为第一道防线。

### 6. 测试矩阵

7 个场景：买家 wallet=ledger、开发者 wallet=ledger、达人 wallet=ledger、达人 10% 扣回、重复退款幂等、达人已提现拒绝退款、无达人订单退款。

## 变更

- `WalletService::record()` 新增 wallets.balance 同步逻辑
- `CreateLedgerOnUserRegistered` 监听器从 Ledger 模块迁移至 Wallet 模块
- `AdminOrderController::refund()` 改走 WalletService，新增达人余额前置检查和佣金回滚
- 移除 AdminOrderController 中不再使用的 LedgerContract 注入
