# 项目开发进度看板

> 最后更新：2026-11-08 | 状态：交易闭环已通，资金链路待加固

## 📊 核心数据

| 维度 | 状态 | 说明 |
|---|---|---|
| 测试基线 | **152 passed / 4 skipped / 511 断言** | 全绿，4 skip 为开发者模块休眠 |
| CI/CD | 已上线 | GitHub Actions |
| 架构守卫 | 12 项全绿 | 模块边界 + 防裸查 |
| 静态分析 | Larastan + Pint | 已接入 |

## 🟢 已完成

### 工程基建
- CI/CD、模块化单体（12 模块）、Contracts 隔离、AGPL-3.0
- Larastan 静态分析、Pint 格式化

### 核心 AI 与账本
- 多 AI 供应商智能路由（17 供应商、14 模型、熔断）
- 复式记账 Ledger（BigInt 毫精度、悲观锁、防重复扣费）
- Wallet 资金缓存层

### 交易闭环
- 商品管理（Admin CRUD、强制下架）
- 订单（创建、支付、取消、退款、分账）
- 单级达人分销（绑定、自动分佣、幂等）

### 支付与提现
- 支付网关抽象（`PaymentGatewayInterface`）+ Mock 实现
- 充值订单创建、回调处理、幂等与金额校验
- 提现审批流（apply / approve / reject / mark-paid）
- 财务中心（充值流水、提现审批、导出）

### 开发者生态（休眠）
- 开发者入驻申请与审核
- 技能全生命周期（草稿→提审→审核→上架→调用）
- API 文档（Scribe）

## 🚧 进行中 / 待办

### P0 · 资金安全（最高优先级）
- [ ] Mock 网关禁止在非 local/testing 环境生效
- [ ] 真实支付宝 / 微信渠道接入

### P1 · 账务一致性
- [ ] `OrderService::payOrder` 改走 `WalletService::deduct`
- [ ] `AdminOrderController::refund` 同步 `wallet.balance`
- [ ] `WithdrawalService` 同步 `wallet.balance`
- [ ] `DeductBalanceOnAiUsage` 同步 `wallet.balance`

### P1 · 测试补强
- [ ] `PaymentNotifyController` 测试（签名、幂等、金额校验）
- [ ] `WithdrawalService` 测试（状态流转、原子退款）

## 📋 未来规划
- 管理操作结构化审计日志（`audit_logs` 表）
- 30 处模块内裸表收敛到 `*QueryService`
- 模块边界白名单改为命名空间级
