# 技术债记录

## 2026-10-25 管理后台 B3-B5 发现

- [ ] **api_key 存储策略不统一**: skill_details.api_key 用 Crypt 加密，
      ai_providers.api_key 是明文 text。建议 ai_providers 也改为 Crypt
      getter/setter，或统一外部密钥管理（如 AWS KMS）。

- [ ] **管理操作无结构化审计**: 吊销 API Key 等操作目前只 Log::info，
      无 audit_logs 表。J 阶段建表后应改为写数据库审计，含 actor/target/ip。

- [ ] **API Key 吊销不立即失效**: api_keys.status=0（吊销）后，
      ApiKeyQueryService::findActiveKeyForUser 查询不过滤 status，
      该 key 仍可通过 API 鉴权。需在 API 鉴权中间件加
      where('status', 1) 或等价检查。
