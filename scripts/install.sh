#!/usr/bin/env bash
#
# 一键初始化脚本：方便开源社区快速跑通本项目
# 用法：bash scripts/install.sh
#
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> [1/5] 检查环境依赖"
command -v php >/dev/null 2>&1 || { echo "错误：未安装 PHP"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "错误：未安装 Composer"; exit 1; }
php -m | grep -q pdo_sqlite || echo "提示：建议启用 pdo_sqlite 扩展以使用默认测试数据库"

echo "==> [2/5] 安装 Composer 依赖"
composer install --no-interaction --prefer-dist

echo "==> [3/5] 初始化环境配置"
if [ ! -f .env ]; then
    cp .env.example .env
    echo "已从 .env.example 创建 .env"
else
    echo ".env 已存在，跳过创建"
fi
php artisan key:generate --force

echo "==> [4/5] 执行数据库迁移与种子数据"
php artisan migrate --seed --force

echo "==> [5/5] 完成！启动开发服务器：php artisan serve"
