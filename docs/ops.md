## 管理后台

- 入口: https://xiaoxia.ren/login
- 权限: users.is_admin = 1
- spatie/laravel-permission 已安装，但当前判定走 is_admin 字段，不用 hasRole

### 页面清单（截至 2026-11-08，约 24 个页面 / 30+ 路由）

| 路径 | 功能 | 状态 |
|------|------|------|
| /admin/dashboard | 仪表盘（统计 + 健康 + 进程 + 最近活动） | ✅ |
| /admin/users | 用户管理（列表 / 详情 / 改角色 / 禁用） | ✅ |
| /admin/products | 商品管理（CRUD / 强制下架） | ✅ |
| /admin/orders | 订单管理（列表 / 退款） | ✅ |
| /admin/finance | 财务中心（充值 / 提现总览） | ✅ |
| /admin/finance/recharges | 充值流水（含导出） | ✅ |
| /admin/finance/withdrawals | 提现审批（approve / mark-paid / reject / 导出） | ✅ |
| /admin/wallets | 钱包管理（列表 / 充值记录） | ✅ |
| /admin/ledger | Ledger 账户与流水（余额按 entries 汇总） | ✅ |
| /admin/skills | 技能管理 + 调用记录 | ✅ |
| /admin/skills/pending | 技能待审核（审核通过/拒绝） | ✅ |
| /admin/api-keys | API Key 管理（吊销生效） | ✅ |
| /admin/ai-providers | AI 供应商（API Key 脱敏 / 模型列表） | ✅ |
| /admin/system/health | 系统健康（DB / Cache / Nginx / FPM / Worker） | ✅ |
| /admin/system/backups | 数据库备份列表（只读） | ✅ |
| /admin/system/logs | 应用日志（tail-100，HTML 转义） | ✅ |

### 管理后台已知边界
- 日志页不提供下载（防敏感信息）
- 备份页不提供下载（路径穿越风险，需单独任务做）
- 管理操作仅 Log::info，无 audit_logs 表（见 tech-debt）
- 后台所有查询走 Service，Controller 禁裸查（架构测试守门）

### 设置管理员
```bash
export MYSQL_PWD=$(grep ^DB_PASSWORD= /var/www/app/.env | cut -d= -f2-)
mysql -u root $(grep ^DB_DATABASE= /var/www/app/.env | cut -d= -f2-) -e "UPDATE users SET is_admin=1 WHERE email=your@email.com"
unset MYSQL_PWD
```

## npm 版本要求（2026-11-14）

- 必须使用 **npm 11+**：`sudo npm install -g npm@11`
- npm 10.9.9 的 arborist 在解析 vitest peer 依赖集时崩溃（`Cannot read properties of null (reading 'edgesOut')`），全新解析的 `npm install` 必触发
- npm 11 已修复，`npm install` 全新解析正常（20s 通过）
- `npm ci` 仍是日常重建依赖首选（快、确定性）
- 当前环境：Node v22.23.5 / npm 11.21.2
