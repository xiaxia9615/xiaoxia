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
└── Ledger/     复式记账、五方分账
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

## API（12 条）

- Auth: register / login / logout / me
- ApiKey: 列表 / 生成 / 吊销
- Skill: 列表 / 详情 / 调用
- Product: 列表 / 详情

统一前缀：/api/v1/

## 本地开发

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

## 测试

php artisan test tests/Architecture

## 文档

- docs/adr/ — 架构决策
- docs/tech-debt.md — 技术债


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
