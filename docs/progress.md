# 项目开发进度看板

> 最后更新：2026-11-23 | 状态：支付宝真实网关已跑通 + SPA 化完成 + 21 模块全量注册，Git HEAD `e8c7b77`

## 📊 核心数据

| 维度 | 状态 | 说明 |
|---|---|---|
| 测试基线 | **168 passed / 635 断言** | 全绿；本仓库历史删除 16 个 Admin/Blade 测试文件（`b906a59`/`6e17c32`/`febedeb`），上一版看板记录的 236 已不适用 |
| CI/CD | 已上线且持续转绿 | GitHub Actions（PHP 8.5 + MySQL 8.0 + Redis 7），tsc 作为唯一强类型安全网 |
| 架构守卫 | 6 规则 + 12 路由预算 | 模块边界 + 防裸查 + 防绕过 WalletService + 路由体积/提交逻辑（`check-architecture.mjs`）；当前 `src/routes/admin/skills.tsx` 待迁移 |
| 静态分析 | Larastan 0 errors + Pint 0 issues | 已接入 |
| 前端质量 | typecheck 0 errors + build 通过 | tsc --noEmit + Vite 构建 |
| 模块数 | **21 个目录 / 18 个注册** | app/Modules/ 下 21 个目录；config/modules.php list 注册 18 个（含 ServiceProvider）；Goods/Favor/Aftersale 仅有 Http/Controllers 目录，无 ServiceProvider，未纳入 list |
| 路由总数 | 110 条（`artisan route:list --json` 口径） | 含 Admin JSON API + API + SPA 兜底 |
| Git 远程 | origin → Gitee + GitHub 双推 | 私有化仓库 |
| Git HEAD | `e8c7b77` | feat(admin): 完善用户封禁与订单分页逻辑，修复金额精度展示 |

## 🟢 已完成

### 支付宝真实网关接入（2026-11-17 ~ 11-22）
- **后端**：`AlipayGateway` 使用 `yansongda/pay ^3.7` SDK 公钥证书模式；`AlipayController` 手写 RSA2 签名实现 OAuth 授权（app_cert_sn + alipay_root_cert_sn）；auth_code 换 token + 获取用户信息；兼容 open_id
- **前端**：二维码弹窗 `pay-qr-dialog.tsx` + 状态轮询 + 主动查单页 `pay-self-check`（解决支付成功但回调失败的死单问题）
- **多轮修复**：GBK 编码 / BOM / 根证书 SN / open_id 兼容 / 时区问题
- **关键 commit**：`b7f3ec4`, `30d576c`, `f4f0bea`

### 多方式登录认证（阶段 1-3，2026-11-16 ~ 11-17）
- **阶段 1 依赖与表结构**：overtrue/easy-sms ^3.5 + laravel/socialite ^5.32 + socialiteproviders/weixin ^4.1 + yansongda/pay ^3.7；users 表新增 provider/provider_id + 联合索引，email/password 改 nullable
- **阶段 2 腾讯云短信 + 手机号登录**：config/easysms.php（qcloud 通道）；SmsService（失败记 Log 抛异常）；AuthController smsSend（⚠️ 频控待补：`config/ratelimit.php` 缺 `sms_per_minute`，当前 SMS 接口无有效限流）+ smsLogin（校验成功立即删 Key 防复用、用户不存在自动创建、签发 Sanctum token、禁用账号 403）
- **阶段 3 OAuth 三方登录**：SocialiteController（GitHub/微信，provider+provider_id 查找或创建用户）；AlipayController（官方网关手写 RSA2 签名，auth_code 换 token + 获取用户信息）；回调统一重定向 `https://xiaoxia.ren/#/auth/callback?token=***&redirect=/`
- **认证中间件**：`UidAuth`（UID 签名认证，兼容 Sanctum Token）+ `EnsureUserIsAdmin` + `EnsureIsFinance` + `EnsureUserIsActive` + `ThrottleByUserOrIp`
- **待用户**：.env 填入真实腾讯云短信密钥并实测发送

### P1 技术债清理：34 处裸表收敛（2026-11-17）
- **Ledger 批次（c0e9e17）**：新建 LedgerQueryService（12 处委托，事务/悲观锁/复式记账逻辑不变）；AdminLedgerService 更名 AdminLedgerQueryService；新建 AiUsageDeductionService；删除死代码 Ledger DeductBalanceOnAiUsage 监听器（消除双重扣款隐患）
- **Skill/Team/System/Order 批次（495b061）**：AdminSkillQueryService、TeamBillingQueryService、SystemQueryService、OrderService 分账幂等检查改走 LedgerQueryService::hasBizEntries
- **验收**：业务代码 DB::table 清零（仅封装服务内部保留）；测试 236 passed / 4 skipped / 796 assertions 与基线一致

### 订单模块后端真实实现 + 前端双态展示（2026-11-17）
- **OrderController 真实实现**：show/orderDetail/orderCancel/orderDelete 全部对接真实数据，新增 toDetailPayload（含 nature/tracking_no/receiver_*/items）与 statusToNumber
- **前端双态展示**：order-detail-page 虚拟商品已支付显示绿色「已自动交付」，收货信息/物流信息区仅实物订单显示
- **越权测试**：新增 3 个测试（查看他人订单 404 / 取消他人订单 400 / 删除他人订单 404）

### 全栈依赖安全升级（2026-11-17）
- **Composer**：46 包升级，composer audit 0 漏洞
- **NPM**：minor/patch 全量 + major 升级（orval 8.x、i18next 27.x、react-i18next 18.x、jsdom 31.x、vitest 5.x、jest-dom 7.x），npm audit 0 漏洞
- **CI 修复**：composer install 前切换官方 Packagist 源 + --prefer-source；npm install 指定官方 registry

### Admin 后台彻底重构（2026-11-15 ~ 11-16）
- **统一布局体系**：抽离 `AdminPageLayout`、`DataTable`、`Pagination`、`StatusBadge` 共享组件，14 个模块路由全部接入
- **AdminShell 升级**：侧边栏 5 组折叠菜单（概览/交易/资产/AI/系统）+ 顶部面包屑
- **仪表盘极客暗黑风**：纯 SVG 折线图、动态数字滚动（use-count-up）、CRT 扫描线效果
- **核心模块真实数据对接**（零 Mock）：财务中心（充值流水/提现审批双 Tab）、用户列表、AI 供应商、技能管理、API Keys、调用流水
- **后端 JSON API 补齐**：4 个模块 7 条新路由（ai-providers/skills/api-keys/api-calls），全部挂 `v1/admin` + `auth:***`
- **后续完善**（`e8c7b77`）：用户封禁逻辑、订单分页、金额精度展示修复、深色模式残留样式修复

### 双态商品与自动发货闭环
- `products` 表新增 `nature`（physical/virtual）、`quota`、`skill_id` 字段
- 支付成功后自动分流：实物订单进入待发货流程，虚拟商品秒级自动发放额度并完成订单
- Admin 商品管理支持双态编辑

### SPA 化完成
- 删除旧 Blade 视图，完全前后端分离
- 前端路由切换为 **hash 模式**（TanStack Router），OAuth 回调页正常渲染
- `beforeLoad` 放行 `/auth/callback`；hash 路由模式下修复 `location.search` 为空问题

### 大厂级安全架构（BFF & 路由守卫）
- **后端 API 全面脱敏**：AI 供应商与 API Keys 输出 `masked_key`（sk-****后4位 / key_prefix+****），绝不返回明文
- **密钥防覆盖**：apiUpdate 的 api_key 留空时保留原值
- **前端路由守卫体系**：
  - `requireAuth`：13 个敏感路由 beforeLoad 强拦截
  - `requireAdmin`：/admin（覆盖全部子路由）+ /source-export，登录 + is_admin 双重校验
  - `requireGuest`：已登录用户访问 /login 时按角色跳转（admin→/admin，finance→/admin/finance，其他→/）
  - 未登录访问后台零骨架渲染，瞬间跳转登录页
- **AuthProvider 加固**：启动校验期间全屏 Loading + 401 事件强制跳转登录页（携带 redirect 回跳参数）

### 前端可观测性体系
- **三类数据**：error / metric / event（TelemetryRecord）
- **性能采集**：FCP/LCP/CLS/INP/TTFB/dom_ready（PerformanceObserver，无第三方依赖）
- **批量上报**：BATCH_SIZE=10, FLUSH_INTERVAL=5000ms, MAX_BUFFER=50，页面隐藏时 sendBeacon 兜底
- **埋点事件**：page_view / product_view / add_to_cart / begin_checkout / place_order / pay_start / pay_result / login / register / search / favorite_toggle / contribute_submit
- **配置**：未配置 `VITE_TELEMETRY_ENDPOINT` 时仅 DEV 打印

### 工程化与质量保障
- 移除历史遗留的 QMuse 依赖及外链图床
- PayPal 支付通道已下线删除（`paypal.ts` 已移除），仅保留支付宝 + 微信
- Pint 格式化 + Larastan 静态分析接入，CI 全量检查项本地对齐
- GitHub Actions CI 流水线彻底转绿
- 变异测试（infection）完成资金链路验证后已清理现场
- OpenAPI schema：`storage/app/private/scribe/openapi.yaml` → `src/lib/api/schema.d.ts`

### 工程基建（历史）
- CI/CD、模块化单体（21 个模块目录，18 个注册于 config/modules.php list 且有 ServiceProvider）、Contracts 隔离、AGPL-3.0
- 模块边界白名单收紧到 Contracts 命名空间级（d8649ab）
- AiProvider Dto/DTOs/ 合并统一（d8649ab）
- System 模块纳入 config/modules.php list + SystemServiceProvider（4e65d80）

### 核心 AI 与账本（历史）
- 多 AI 供应商智能路由（17 供应商、11 模型、熔断）
- 复式记账 Ledger（BigInt 毫精度、悲观锁、防重复扣费）
- Wallet 资金缓存层

### 交易闭环（历史）
- 商品管理（Admin CRUD、强制下架、审计日志）
- 订单（创建、支付、取消、退款、分账）
- 单级达人分销（绑定、自动分佣、幂等）

### 支付与提现（历史）
- 支付网关抽象（`PaymentGatewayInterface`）+ Mock 实现（仅 local/testing）
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

## 📋 模块清单（21 个）

**app/Modules/ 目录（21 个）**：Address, Aftersale, AiProvider, ApiKey, Article, Browse, Cart, Comment, Distributor, Favor, Goods, Ledger, Message, Order, Payment, Product, Skill, System, Team, User, Wallet

**config/modules.php list 注册（18 个，均有 ServiceProvider）**：User, Product, ApiKey, Skill, Ledger, AiProvider, Team, Wallet, Payment, Order, Distributor, Address, Cart, Article, Browse, Comment, Message, System

> ⚠️ Goods、Favor、Aftersale 三个模块仅有 `Http/Controllers/` 目录（各一个控制器），无 ServiceProvider、无 Models/Services，未纳入 config/modules.php list 注册。其中 Goods 在 dependencies 中声明了依赖（`'Goods' => ['Product']`），Favor 和 Aftersale 在 config/modules.php 中完全未出现。

## 📋 后端中间件（5 个）

- `UidAuth` — UID 签名认证（兼容 Sanctum Token）
- `EnsureUserIsAdmin` — 管理员校验 is_admin
- `EnsureIsFinance` — 财务角色校验 is_finance
- `EnsureUserIsActive` — 账号启用状态校验
- `ThrottleByUserOrIp` — 短信频控（手机号 60s/次 + IP 每小时 5 次）

## 📋 前端功能模块（11 个 feature）

| 模块 | 组件 | Hooks | 状态 |
|---|---|---|---|
| home | home-page, zhen-hero, zhen-sections, bagua-compass, stream-terminal | useBagua | ✅ 完整 |
| catalog | product-card | use-product-detail | ✅ 完整 |
| cart | — | use-cart | ✅ 本地 localStorage + 登录走 API |
| checkout | checkout-page | use-place-order | ✅ 含虚拟商品逻辑 |
| order | orders-page, order-detail-page, aftersales-page | useOrders, useOrderDetail, useAftersales | ✅ 完整 |
| payment | pay-qr-dialog, pay-self-check-page | usePaySelfCheck | ✅ 支付宝二维码 + 轮询 + 主动查单 |
| account | login-page, messages-page, profile-page, reset-password-dialog, auth.callback | use-auth-forms, use-address-book, use-messages, use-profile-forms, use-require-login | ✅ 登录/回调完整，资料编辑/改密/重置密码为空实现 |
| assistant | — | useChat | ✅ 本地关键词规则匹配（非 LLM） |
| article | — | — | ✅ 完整 |
| admin | AdminShell, AdminPageLayout, DataTable, Pagination, StatusBadge, crt-overlay | — | ✅ 14 模块真实数据 |
| source-export | source-export-page | — | ⚠️ 临时工具页 |

## 📋 路由结构

- **公开**：`v1/article/*`, `v1/goods/*`, `v1/security/publickey`, `v1/verify/send`
- **登录（uid.auth）**：`v1/cart/*`, `v1/address/*`, `v1/favor/*`, `v1/message/*`, `v1/browse`, `v1/aftersale/*`, `v1/order/*`, `v1/orders/*`, `v1/products/categories`
- **管理员（uid.auth + admin）**：`v1/admin/{orders,users,products,finance,ai-providers,skills,api-keys,api-calls,dashboard}/*`
- **支付回调（无需登录）**：`v1/payment/notify/{wechat,alipay}`
- **开发环境 fallback**：非 production 返回 mock data

## 🚧 进行中 / 待办

### 阶段 4 · 前端登录开发（✅ 基本完成）
- [x] auth.callback 页 token 处理与跳转（hash 路由模式解析 token → localStorage → fetchProfile → 按角色跳转）
- [x] 登录页短信登录表单（手机号验证码 + 60s 倒计时 + 429 提示）
- [x] 登录页第三方登录入口（GitHub/微信/支付宝 OAuth 跳转）
- [ ] 登录页细节打磨与实测（需 .env 配置真实短信密钥）

### 个人资料功能
- [ ] `use-profile-forms.ts` 中 onSubmitProfile / onSubmitPassword 提交逻辑为空实现（受 AUTH_FEATURES 开关控制）
- [ ] `reset-password-dialog.tsx` 重置密码功能为空实现
- [ ] 后端对应 API 对接

### P0 · 支付接入
- [ ] 真实微信支付渠道接入（`WechatPayGateway.php` 骨架已存在，需商户资质）
- [ ] 退款分账回滚（开发者模块开放后）

### P2 · 技术债
- [ ] 15 个前端业务文件超 100 行拆分（product-form-modal 420 / login-page 359 / products-page 287 / products_.$productId 259 / finance-page 250 / address-dialog 239 等）；原 6 个中 5 个已拆，products 143→287 反而上升
- [ ] 历史资金数据回填脚本（涉及资金，建议人工复核）

### P1 · C 端打磨
- [ ] C 端页面体验优化（首页/商品/购物流程细节打磨）

## 📌 Git 提交历史（近期）

| Commit | 说明 |
|--------|------|
| `e8c7b77` | feat(admin): 完善用户封禁与订单分页逻辑，修复金额精度展示 |
| `b7f3ec4` | feat(payment): 支付宝真实网关接入 |
| `30d576c` | feat(payment): 前端二维码弹窗与状态轮询 |
| `f4f0bea` | feat(payment): 主动查单兜底机制 |
| `495b061` | refactor(modules): converge remaining raw table access into query services |
| `c0e9e17` | refactor(ledger): converge raw table access into query services |
| `71930f4` | docs: mark Skill/Team contracts tech debt as resolved |
| `16340e8` | chore: remove QMuse remnants |
| `73822ca` | ci: use official registries for composer and npm in GitHub Actions |
| `6e257a5` | ci: use --prefer-source to avoid tencent mirror dist URL 404 |
| `c9116ad` | chore: upgrade all composer and npm dependencies (0 vulnerabilities) |
| `577b115` | fix(ci): resolve 4 phpstan errors |
| `d16749c` | fix(auth): requireAdmin guard for admin and source-export |
| `b93b5b2` | feat(auth): global route guards with requireAuth, auth loading gate and 401 redirect |
| `1e6b80b` | feat(admin): JSON APIs and real-data pages for ai-providers, skills, api-keys and api-calls |
| `84ecce2` | feat(admin): finance milli-amount display, reject reason form, grouped sidebar with breadcrumb |
| `f00d38d` | style: fix formatting and remove infection |
| `a37c713` | fix(ci): resolve larastan errors with model phpdoc and closure fixes |
| `2b1ec65` | feat(order): dual-nature products with auto-delivery and shipment |
| `4361199` | dashboard hooks 迁移基线 |