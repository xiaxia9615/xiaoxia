# 项目开发进度看板

> 最后更新：2026-11-24 | 状态：支付宝真实网关已跑通 + SPA 化完成 + 微信小程序/H5 静默登录接入，21 目录 / 18 模块注册，Git HEAD `aaf3159`

## 📊 核心数据

| 维度 | 状态 | 说明 |
|---|---|---|
| 测试基线 | **168 passed / 635 断言** | 全绿；本仓库历史删除 16 个 Admin/Blade 测试文件（`b906a59`/`6e17c32`/`febedeb`），上一版看板记录的 236 已不适用 |
| CI/CD | 已上线且持续转绿 | GitHub Actions（PHP 8.5 + MySQL 8.0 + Redis 7），tsc 作为唯一强类型安全网 |
| 架构守卫 | 6 规则 + 12 路由预算 | 模块边界 + 防裸查 + 防绕过 WalletService + 路由体积/提交逻辑（`check-architecture.mjs`）；当前 `src/routes/admin/skills.tsx` 待迁移 |
| 静态分析 | Larastan 0 errors + Pint 0 issues | 已接入 |
| 前端质量 | typecheck 0 errors + build 通过 | tsc --noEmit + Vite 构建 |
| 模块数 | **21 个目录 / 18 个注册** | app/Modules/ 下 21 个目录；config/modules.php list 注册 18 个（含 ServiceProvider）；Goods/Favor/Aftersale 仅有 Http/Controllers 目录，无 ServiceProvider，未纳入 list |
| 路由总数 | 111 条（`artisan route:list --json` 口径） | 含 Admin JSON API + API + SPA 兜底 |
| Git 远程 | origin → Gitee + GitHub 双推 | 私有化仓库 |
| Git HEAD | `aaf3159` | feat(wechat): 完成前端H5静默登录接入与打包配置 |

## 🟢 已完成

### 微信小程序 / H5 静默登录
- 后端 `WechatMiniController`：通过 code2Session 获取 openid/unionid 并与 H5 账号打通；users 表新增 `unionid` 字段（`34c8276`）
- 前端 `wechat-auth.ts`：微信内置浏览器 UA 检测，URL 携带 code 时静默换取 Token，存储方式与普通登录一致（`aaf3159`）

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

## 📋 前端功能模块（11 个 feature）

| 模块 | 组件 | Hooks | 状态 |
|---|---|---|---|
| home | home-page, zhen-hero, zhen-sections, bagua-compass, stream-terminal | useBagua | ✅ 完整 |
| catalog | product-card | use-product-detail | ✅ 完整 |
| cart | — | use-cart | ✅ 本地 localStorage + 登录走 API |
| checkout | checkout-page | use-place-order | ✅ 含虚拟商品逻辑 |
| order | orders-page, order-detail-page, aftersales-page | useOrders, useOrderDetail, useAftersales | ✅ 完整 |
| payment | pay-qr-dialog, pay-self-check-page | usePaySelfCheck | ✅ 支付宝二维码 + 轮询 + 主动查单 |
| account | login-page, messages-page, profile-page, reset-password-dialog, auth.callback | use-auth-forms, use-address-book, use-messages, use-profile-forms, use-require-login | ✅ 登录/回调/微信静默登录完整，资料编辑/改密/重置密码为空实现 |
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
- [ ] 前端业务文件超 100 行拆分：Top 3 已完成（product-form-modal 420→350+schema 78 / login-page 359→219+icons 30+phone-form 117 / products-page 287→245+filter-bar 69），剩余 35 个（products_.$productId 259 / finance-page 250 / address-dialog 239 / zhen-sections 221 / header 220 等）
- [ ] 历史资金数据回填脚本（涉及资金，建议人工复核）

### P1 · C 端打磨
- [ ] C 端页面体验优化（首页/商品/购物流程细节打磨）
