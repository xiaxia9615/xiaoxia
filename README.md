# 嗣夏

多店铺技能与商品交易平台。

- 域名：https://xiaoxia.ren
- 定位：技能上架 + 商品交易 + 达人分销 + 公益分账

## 技术栈

- Laravel 13
- PHP 8.5
- MySQL 8
- Redis
- Nginx

## 目录结构

app/Models/          模型（User / Product / ApiKey）
database/migrations/ 数据库迁移
routes/api.php       API 路由

## 本地开发

composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

## 部署

git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

## 许可

私有项目
