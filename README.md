# 嗣夏（Xiaoxia）
自营模式的 AI 技能与能力服务平台，基于 Laravel 13 打造的高性能、金融级 AI 交易底座。

域名：https://xiaoxia.ren

## 合规与模式说明
本项目主体为**个体工商户**（栖霞区青丝嗣夏百货中心），采用**自营模式**。平台官方自营上架 AI 技能，用户充值后购买。
平台保留**单级达人分销**体系（严格遵循合规要求，禁止多级分销）。开发者入驻模块已开发完毕，但目前暂未开放（API 隐藏，代码休眠）。

## 快速开始

### 环境要求
- PHP 8.3+
- Composer 2.x
- Node.js 20+ / npm 10+
- Redis 7+（默认 phpredis，无 ext-redis 则改 REDIS_CLIENT=predis）
- 数据库：默认 SQLite（开箱即用），生产推荐 MySQL 8

### 一键跑通（开发模式，默认 SQLite）

```bash
git clone https://gitee.com/yang-satan-satan/xiaoxia.git
cd xiaoxia
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

### 创建管理员

本项目不提供默认管理员账号。首次部署后执行：

```bash
php artisan tinker
>>> App\Models\User::create(['name'=>'Admin','email'=>'you@x.com','password'=>bcrypt('密码'),'is_admin'=>true]);
```

### 常见问题
- **PHP 版本** → 要求 PHP 8.5（最低 8.3）
- **Redis 连不上** → 默认 phpredis 需 ext-redis；没装就改 `.env` 的 `REDIS_CLIENT=predis`
- **前端白屏** → 先 `npm run build`，访问 Laravel 端口（非 Vite 5173）
- **后台 403** → `users` 表 `is_admin=1` 才是管理员
- **CSS 错乱** → 不要改 `vite.config.js` 的 `css.transformer`

> **商用路径**：如需闭源商业部署，请联系 al@qssxmall.cn 获取商业许可，免除 AGPL-3.0 开源义务。

## 技术栈
- **后端**：Laravel 13 · PHP 8.5 · MySQL 8 · Redis · Nginx
- **前端**：React 18 · TypeScript · Vite · TanStack Router（SPA，内嵌于 Laravel Blade）

## 核心模块（21 个）
**交易核心**：User（用户） / Product（商品） / Order（订单） / Payment（支付） / Wallet（钱包） / Ledger（复式记账）
**AI 能力**：Skill（技能） / ApiKey（鉴权） / AiProvider（AI 路由，17 供应商 / 11 模型）
**业务扩展**：Cart（购物车） / Address（地址） / Favor（收藏） / Comment（评论） / Article（文章） / Message（消息） / Browse（浏览记录） / Aftersale（售后） / Goods（商品扩展）
**渠道与计费**：Distributor（单级分销） / Team（团队计费） / System（后台管理）

## 核心能力
- **电商交易闭环**：商品管理 ➡️ 创建订单 ➡️ 支付 ➡️ 退款回滚。
- **支付网关**：接口契约、订单创建、回调处理、幂等与金额校验已完成，Mock 网关可跑通全流程；支付宝 / 微信渠道实现待接入。
- **提现审批流**：申请 → 审批 → 打款 / 拒绝（含原子退款）。
- **单级达人分销**：绑定关系、自动按比例计算达人佣金。
- **金融级复式记账**：底层 BigInt（毫）精度，悲观锁防超扣。
- **多 AI 供应商路由**：17 个供应商，11 个模型入库，支持熔断。

## 管理后台
入口：`/admin`（需管理员登录）
已实现 24 个页面（含订单管理、商品管理、财务中心、钱包流水、提现审批等）。

## 测试与质量
**当前基线**：`236 passed / 4 skipped`（796 个断言），全绿。
CI：GitHub Actions（PHP 8.5 + MySQL 8 + Redis）自动跑全量测试 + 13 项架构守卫（21 assertions）。静态分析：Larastan 0 errors + Pint 0 issues。

## 文档索引
| 文档 | 说明 |
|------|------|
| [docs/progress.md](docs/progress.md) | 项目进度看板 |
| [docs/tech-debt.md](docs/tech-debt.md) | 技术债追踪 |
| [docs/ops.md](docs/ops.md) | 运维手册 |
| [docs/health-log.md](docs/health-log.md) | 自动体检日志 |
| [docs/adr/](docs/adr/) | 架构决策记录 |
| [CONTRIBUTING.md](CONTRIBUTING.md) | 贡献指南 |
| [CLAUDE.md](CLAUDE.md) | AI Agent 规则 |
| [CHANGELOG.md](CHANGELOG.md) | 变更日志 |

## 许可与联系
AGPL-3.0 + 商业许可 双轨模式。
**商业联系**：al@qssxmall.cn
**技术咨询**：2444160808@qq.com

Copyright (c) 2026 嗣夏平台.
