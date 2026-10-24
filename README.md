# 嗣夏

多店铺技能与商品交易平台：技能上架 + 商品交易 + 达人分销 + 公益分账

域名：https://xiaoxia.ren

## 技术栈

Laravel 13 · PHP 8.5 · MySQL 8 · Redis · Nginx

## 架构：模块化单体

\`\`\`
app/Modules/
├── User/       用户、认证
├── Product/    商品与技能
├── ApiKey/     API Key 与调用记录
├── Skill/      技能详情、调用代理
├── Ledger/     复式记账、五方分账
├── AiProvider/ AI 供应商配置与模型路由
└── Team/       团队与团队计费（骨架，未开放路由）
\`\`\`

核心规则：
- 每个模块独立命名空间 App\Modules\{模块}
- 模块间依赖通过 config/modules.php 白名单声明
- 老 app/Models/* 为兼容壳，新代码用 App\Modules\*\Models\*
- 架构守卫 tests/Architecture/ 自动拦截越界

## 核心能力

- 技能调用闭环：POST /api/v1/skills/{id}/call
- 五方分账：提供者 / 平台 / 达人 / 公益 / 调用方
- 复式记账：ledger_accounts + ledger_entries，借贷相等

## API（18 条）

- Auth: register / login / logout / me
- ApiKey: 列表 / 生成 / 吊销
- Skill: 列表 / 详情 / 创建 / 审核 / 调用 / 调用记录 / 网关
- Product: 列表 / 详情

统一前缀：/api/v1/

## 管理后台

入口：https://xiaoxia.ren/login
权限：`users.is_admin = 1` 可访问（spatie 角色保留，当前判定用 is_admin 字段）

已实现页面：
- `/admin/dashboard`   仪表盘（统计卡片 + 系统健康 + 服务进程 + 最近活动）
- `/admin/users`       用户管理（列表 / 详情 / 改角色 / 禁用）
- `/admin/ledger`      Ledger 账户与流水（余额按 ledger_entries 汇总）

待实现：AI 供应商 / 技能 / API Keys / 系统页

## 本地开发

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

## 测试

php artisan test                        # 全量测试
php artisan test tests/Architecture     # 架构守卫

当前基线：83 passed / 368 assertions

## 文档

- docs/adr/           架构决策记录
- docs/tech-debt.md   技术债清单
- docs/health-log.md  健康度基线日志
- docs/ops.md         运维手册（部署 / 备份 / 证书 / 管理后台）

## 开发状态

- 工程基建：完备（83 测试 / 架构守卫 / 自检 / 每日备份 / Let's Encrypt 证书）
- 管理后台：进行中（4/8 页面完成：登录 / 仪表盘 / 用户 / Ledger）
- 功能层：骨架完整，部分模块待填充（Ledger 无业务数据、Team 未开放路由）
- 产品方向：规划中


## 许可

Copyright (c) 2026 嗣夏平台. All rights reserved.

本软件及其源代码、架构设计、文档均为嗣夏平台的专有财产，受著作权法保护。

未经著作权人事先书面许可，任何个人或组织不得：
- 复制、修改、分发本软件或其任何部分
- 将本软件用于商业用途
- 移除或修改本软件中的版权声明

This software and its source code, architecture design, and documentation are the proprietary property of 嗣夏平台, protected by copyright law.

No part of this software may be reproduced, modified, distributed, or used for commercial purposes without prior written permission from the copyright holder.

详见 [LICENSE.txt](LICENSE.txt)。
