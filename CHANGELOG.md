# 变更日志

本项目所有重要变更记录于此文件。
格式参考 [Keep a Changelog](https://keepachangelog.com/)。

---

## 2026-11-13

### Features
- 完成 Admin 后台全量功能：技能配置中心 CRUD、API 调用流水审计、队列监控面板（`83e851f`）

### Documentation
- 同步技术债状态：所有 P0/P1 资金安全项已修复，架构债已更新（`f9bbba7`）
- 同步项目进度：224 测试、18 模块、Admin 功能完成（`cc63fcc`）
- 更新自动体检日志 Stage-1 结果（`523a5e9`）

---

## 2026-11-12

### Features
- 完成最后 4 个后端模块（Article、Browse、Comment、Message），200 测试全绿（`3eccd8b`）
- 完成 Admin 仪表盘真实数据绑定，全量测试通过（`31d7ea3`）

### Refactoring
- 解决 P0-2 技术债：统一测试充值走 WalletService，224 测试全绿（`52c5bc6`）
- 解决 P2 结构性技术债：强制命名空间级边界（`d8649ab`）
- 提交 P2 DTO 迁移和 System Provider 缺失文件（`4e65d80`）

### Chores
- 包含前端架构修复脚本并更新模块依赖（`5a7c932`）

---

## 2026-11-10

### Features
- 实现购物车和地址模块，修复 auth guard，186 测试通过（`4ef0aed`）
- 完成 40 个缺失 API 端点骨架，同步路由和文档，修复 404 回退（`4795862`）
- 添加 React SPA 入口（`3a571a7`）

### Bug Fixes
- 修复存储权限，更新 SPA 示例测试，186 测试全绿（`096a30b`）
- 修复前端所有 TypeScript 错误和依赖（`daa05ce`）
- 加固支付网关并配置 PHPUnit Mock 测试（`667c2c2`）

### Build
- 解决所有 TS 错误，修复依赖并构建生产资源（`4963757`）
- 同步前端构建配置、SPA 路由和 tsconfig（`dc42e3c`）

### Quality
- 100% 测试覆盖率，0 错误，准备 v1.0.0 发布（`13ebda0`）

---

## 2026-11-09

### Documentation
- 生成 API 文档（Blade 视图、静态资源、Postman 集合、OpenAPI 规范）（`3a92bde`）

### Quality
- 解决所有剩余 PHPStan 错误（0 errors）（`ced75dc`）
- 为多个模型添加 @property PHPDoc 注解（`9bc589f` `5be04a3` `1da929b` `98167d3` `d0880ff`）
- 收窄关联返回类型（AiModel::provider, ApiCall::user）（`01bf00c`）
- 标注 AiProvider api_key 为 string|null（`e947769`）

### Bug Fixes
- 修复 AI 模块空 api_key 防护（`47ed96b`）
