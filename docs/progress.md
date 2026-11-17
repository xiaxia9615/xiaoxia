# 项目开发进度看板

> 最后更新：2026-11-17 | 状态：订单模块后端真实实现 + 前端双态展示完成，全栈依赖安全升级（0 漏洞）

## 📊 核心数据

| 维度 | 状态 | 说明 |
|---|---|---|
| 测试基线 | **236 passed / 4 skipped / 796 断言** | 全绿，4 skip 为开发者模块休眠 |
| CI/CD | 已上线且持续转绿 | GitHub Actions（PHP 8.5 + MySQL 8 + Redis） |
| 架构守卫 | 13 项全绿 | 模块边界 + 防裸查 + 防绕过 WalletService |
| 静态分析 | Larastan 0 errors + Pint 0 issues | 已接入 |
| 前端质量 | typecheck 0 errors + build 通过 | tsc --noEmit + Vite 构建 |
| 模块数 | 18 个 | config/modules.php list 全量注册 |
| 路由总数 | 147+ 条 | 含 Admin JSON API + API + SPA 兜底 |
| Git 远程 | origin → Gitee + GitHub 双推 | 私有化仓库 |

## 🟢 已完成

### 订单模块后端真实实现 + 前端双态展示（2026-11-17）
- **OrderController 真实实现**：show/orderDetail/orderCancel/orderDelete 全部对接真实数据（validate id → user_id 查询 → 404/payload），新增 toDetailPayload（含 nature/tracking_no/receiver_*/items）与 statusToNumber 私有方法
- **前端双态展示**：order-detail-page 虚拟商品已支付显示绿色「已自动交付」，收货信息/物流信息区仅实物订单显示（tracking_no 或待发货提示）
- **orders.ts 契约补齐**：OrderDetailDto/OrderDetailData 补 nature/tracking_no 字段，toOrderDetail 映射完成
- **越权测试**：新增 3 个测试（查看他人订单 404 / 取消他人订单 400 / 删除他人订单 404），越权校验严格

### 全栈依赖安全升级（2026-11-17）
- **Composer**：46 包升级（phpstan/larastan/sanctum/predis/spatie-permission 等），composer audit 0 漏洞
- **NPM**：minor/patch 全量 + major 升级（orval 8.x、i18next 27.x、react-i18next 18.x、jsdom 31.x、vitest 5.x、jest-dom 7.x）
- **npm audit**：17 漏洞（11 critical）→ **0 漏洞**
- **CI 修复**：composer install 前切换官方 Packagist 源 + --prefer-source；npm install 指定官方 registry（解决 lock 文件国内镜像 URL 在 GitHub Actions 上 404/超时）
- **验证全绿**：php artisan test 236 passed / phpstan 0 errors / pint 301 files PASS / typecheck + build 通过

### Admin 后台彻底重构（2026-11-15 ~ 11-16）
- **统一布局体系**：抽离 `AdminPageLayout`、`DataTable`、`Pagination`、`StatusBadge` 共享组件，14 个模块路由全部接入
- **AdminShell 升级**：侧边栏 5 组折叠菜单（概览/交易/资产/AI/系统）+ 顶部面包屑，路由切换自动展开当前分组
- **仪表盘极客暗黑风**：纯 SVG 折线图、动态数字滚动（use-count-up）、CRT 扫描线效果
- **核心模块真实数据对接**（零 Mock）：
  - 财务中心：充值流水/提现审批双 Tab，金额毫转元（/1000），驳回原因表单化（必填、max 255），审批确认框
  - 用户列表：真实分页数据
  - AI 供应商：列表（masked_key 脱敏）+ 编辑弹窗（base_url/api_key/enabled）
  - 技能：列表 + 上架/下架切换
  - API Keys：列表（key_prefix 脱敏）+ 吊销二次确认
  - 调用流水：真实 token 消耗与耗时展示
- **后端 JSON API 补齐**：4 个模块 7 条新路由（ai-providers/skills/api-keys/api-calls），全部挂 `v1/admin` + `auth:sanctum`

### 双态商品与自动发货闭环
- `products` 表新增 `nature`（physical/virtual）、`quota`、`skill_id` 字段
- 支付成功后自动分流：实物订单进入待发货流程，虚拟商品秒级自动发放额度并完成订单
- Admin 商品管理支持双态编辑（封面 URL、额度、技能绑定）

### 大厂级安全架构（BFF & 路由守卫）
- **后端 API 全面脱敏**：AI 供应商与 API Keys 输出 `masked_key`（sk-****后4位 / key_prefix+****），绝不返回明文
- **密钥防覆盖**：apiUpdate 的 api_key 留空时保留原值，绝不盲目覆盖
- **前端路由守卫体系**：
  - `requireAuth`：13 个敏感路由（profile/orders/checkout/cart/favorites/address/messages/aftersales/integral/browse-history/pay-self-check/assistant）beforeLoad 强拦截
  - `requireAdmin`：/admin（覆盖全部子路由）+ /source-export，登录 + is_admin 双重校验
  - 未登录访问后台零骨架渲染，瞬间跳转登录页
- **AuthProvider 加固**：启动校验期间全屏 Loading（防骨架闪现）+ 401 事件强制跳转登录页（携带 redirect 回跳参数）

### 工程化与质量保障
- 移除历史遗留的 QMuse 依赖及外链图床
- 后端测试 224 → 233 个全绿（790 断言）
- Pint 格式化 + Larastan 静态分析接入，CI 全量检查项本地对齐
- GitHub Actions CI 流水线彻底转绿，连续多次通过
- 变异测试（infection）完成资金链路验证后已清理现场（不留 CI 负担）

### 工程基建（历史）
- CI/CD、模块化单体（18 模块）、Contracts 隔离、AGPL-3.0
- 模块边界白名单收紧到 Contracts 命名空间级（d8649ab）
- AiProvider Dto/DTOs/ 合并统一（d8649ab）
- System 模块纳入 config/modules.php list + SystemServiceProvider（4e65d80）

### 核心 AI 与账本（历史）
- 多 AI 供应商智能路由（17 供应商、14 模型、熔断）
- 复式记账 Ledger（BigInt 毫精度、悲观锁、防重复扣费）
- Wallet 资金缓存层

### 交易闭环（历史）
- 商品管理（Admin CRUD、强制下架、审计日志）
- 订单（创建、支付、取消、退款、分账）
- 单级达人分销（绑定、自动分佣、幂等）

### 支付与提现（历史）
- 支付网关抽象（`PaymentGatewayInterface`）+ Mock 实现
- 充值订单创建、回调处理、幂等与金额校验
- 提现审批流（apply / approve / reject / mark-paid）

### 资金安全加固（P0/P1 已修复，历史）
- [x] Mock 网关禁止在非 local/testing 环境生效（f758698）
- [x] `OrderService::payOrder` 改走 `WalletService::deduct`（eae1c1e）
- [x] `AdminOrderController::refund` 买家退款同步 `wallet.balance`（7263353）
- [x] `WithdrawalService` 改走 `WalletService::deductFunds/refundFunds`（52c5bc6）
- [x] `DeductBalanceOnAiUsage` 改走 `WalletService`（52c5bc6）
- [x] `SkillCallService` / `TeamBillingService` 全部改走 `WalletService`（52c5bc6）

### 审计与运维（历史）
- 管理操作审计日志（`audit_logs` 表 + `/admin/audit-logs` 页面）
- Queue Worker 健康检查支持 sync 模式
- 系统健康检查（MySQL/Redis/Nginx/PHP-FPM/Worker）
- 队列监控 + 失败重试

## 🚧 进行中 / 待办

### P0 · 支付接入
- [ ] 真实支付宝 / 微信渠道接入
- [ ] 退款分账回滚（开发者模块开放后）

### P1 · C 端打磨
- [ ] C 端页面体验优化（首页/商品/购物流程细节打磨）

## 📌 Git 提交历史（近期）

| Commit | 说明 |
|--------|------|
| `73822ca` | ci: use official registries for composer and npm in GitHub Actions |
| `6e257a5` | ci: use --prefer-source to avoid tencent mirror dist URL 404 on GitHub Actions |
| `c9116ad` | chore: upgrade all composer and npm dependencies (0 vulnerabilities) |
| `577b115` | fix(ci): resolve 4 phpstan errors (env→config, use imports, redirect type) |
| `d16749c` | fix(auth): requireAdmin guard for admin and source-export, requireAuth for assistant |
| `b93b5b2` | feat(auth): global route guards with requireAuth, auth loading gate and 401 redirect |
| `1e6b80b` | feat(admin): JSON APIs and real-data pages for ai-providers, skills, api-keys and api-calls |
| `84ecce2` | feat(admin): finance milli-amount display, reject reason form, grouped sidebar with breadcrumb |
| `f00d38d` | style: fix formatting and remove infection |
| `a37c713` | fix(ci): resolve larastan errors with model phpdoc and closure fixes |
| `2b1ec65` | feat(order): dual-nature products with auto-delivery and shipment |
| `4361199` | dashboard hooks 迁移基线 |
