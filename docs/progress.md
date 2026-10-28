# 项目进度

> 最后更新：2026-10-28 | 分支：`fix/module-boundary-cleanup`

## 状态：能跑，但不健康

| 维度 | 分数 | 说明 |
|---|---|---|
| 运行 | 9/10 | 12 项测试全绿，可继续迭代 |
| 架构 | 6/10 | 约 10 处跨模块内部类引用藏在白名单里 |
| 治理 | 4/10 | 白名单是「收纳箱」而非「例外清单」 |

**一句话**：体检全正常，但知道有隐患。D1 之后会把隐患暴露出来。

## 已完成（D0 快照）

- ✅ 全项目 tar + 数据库全量 dump + 环境基线 + 异地备份
- ✅ 3 个待改测试方法源码 + 正则分析 + System 位置判定
- ✅ 备份位置：`.backup-d0/`（本机）、`/var/backups/t3-step1-d0/.current/`（异地）
- ✅ 分支已推送：`fix/module-boundary-cleanup`

## 已完成（T3-STEP-1）

- ✅ 本机备份 `.backup-d0/` 与异地备份 `/var/backups/t3-step1-d0/.current/` 均在位
- ✅ 本地 HEAD 与远端 `origin/fix/module-boundary-cleanup` 同步于 `54e47fa`，无 ahead/behind
- ✅ 2026-10-28 18:48 只读诊断（READ-ONLY-PROBE）：progress.md 共 20 行，无 EOF 残留；工作区仅 progress.md 未跟踪

## 下一步：D1 改测试判断逻辑

**问题**：白名单现在是模块级的。一条 `Skill => [ApiKey, Product]` 同时放行两种引用：

- ✅ 合规：`use ApiKey\\Contracts\\ApiCallContract`（走契约）
- ❌ 违规：`use ApiKey\\Services\\AdminApiKeyService`（直连内部类）

**所以 12 项全绿，但违规真实存在。**

**改法**：白名单改为命名空间级，只允许 `ApiKey\\Contracts` 和 `Product\\Contracts`。

**预期**：测试从「12 全绿」变成「N 条红」。**N 就是真实违规总量**，这是后续修复的基线。

**报红不是失败，是第一次真实测量。** 每修一个违规，红数减一，直到重新全绿——这次的全绿才是真的。

## 遗留决策（不阻塞当前）

1. System 是否纳入 `config/modules.php` 的 `list`——当前 in_list=NO / in_dependencies=YES，两处不一致
2. .env 异地备份——目前只在同机，单机故障即失
3. mysqldump 加密——目前只 `chmod 600`

## 路线图

```
阶段 1：机制根治（当前）
  D0 快照 ✅
  D1 改测试判断逻辑 ← 下一步
  D2 改白名单格式
  D3 验证故意违规会报红
  D4 生成报告

阶段 2：修存量违规（T2）
  - Skill → AiProvider 三处内部类
  - Ledger 补 Contracts（一次修 Skill/Team 两处）
  - User 事件迁入 Contracts
  - System 定位决策

阶段 3：白名单审计
阶段 4：裸表收敛
```

## 历史决策

- 2026-10-28：`.backup-d0/` 和 `reports/` 不纳入 git 追踪
- 2026-10-28：分支 `fix/module-boundary-cleanup` 基于 main `b2d6ea3`
