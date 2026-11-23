# 嗣夏（Xiaoxia）

一个正在开发中的个人 Web 项目（基于 Laravel 和 React）。

## 技术栈
- 后端：Laravel 13 / MySQL / Redis
- 前端：React 19 / TypeScript / Vite

## 包含功能
- 用户注册与登录
- 商品展示与下单
- 简单的余额扣费系统
- 支付宝支付接入（沙箱测试完毕）

## 运行方式
1. `composer install`
2. `npm install`
3. `cp .env.example .env` 并配置数据库
4. `php artisan key:generate`
5. `php artisan migrate`
6. `npm run build`
7. `php artisan serve`

## 备注
- 个人业余时间开发，还在完善中。
- 目前重点在核心交易链路，部分页面可能还不完整。
