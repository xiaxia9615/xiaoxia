# 技术债记录

## 2026-10-25 管理后台 B3-B5 发现

- [ ] **api_key 存储策略不统一**: skill_details.api_key 用 Crypt 加密，
      ai_providers.api_key 是明文 text。建议 ai_providers 也改为 Crypt
      getter/setter，或统一外部密钥管理（如 AWS KMS）。

- [ ] **管理操作无结构化审计**: 吊销 API Key 等操作目前只 Log::info，
      无 audit_logs 表。J 阶段建表后应改为写数据库审计，含 actor/target/ip。

- [x] ~~API Key 吊销不立即失效~~ — 2026-10-25 修复：
      ApiKeyQueryService::findActiveKeyForUser 查询加 where('status', 1)，
      吊销后的 key 不再通过 skill 调用前置检查（业务逻辑一致性修复，
      鉴权层 findByPlain 本就过滤 status）。测试 98 passed / 0 skipped。
