## 管理后台

- 入口: https://xiaoxia.ren/login
- 权限: users.is_admin = 1
- spatie/laravel-permission 已安装，但当前判定走 is_admin 字段，不用 hasRole

### 已实现页面
| 路径 | 说明 |
|------|------|
| /admin/dashboard | 统计 + 健康 + 进程 + 最近活动 |
| /admin/users | 用户列表 / 详情 / 改角色 / 禁用 |
| /admin/ledger | 账户列表 + 流水（余额按 entries 汇总）|

### 待实现页面
/admin/ai-providers（B3）/ /admin/skills（B4）/ /admin/api-keys（B5）/ /admin/system（B6）

### 设置管理员
```bash
export MYSQL_PWD=$(grep ^DB_PASSWORD= /var/www/app/.env | cut -d= -f2-)
mysql -u root $(grep ^DB_DATABASE= /var/www/app/.env | cut -d= -f2-) -e "UPDATE users SET is_admin=1 WHERE email=your@email.com"
unset MYSQL_PWD
```
