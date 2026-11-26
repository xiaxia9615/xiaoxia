/**
 * Admin 后台 API 请求层（商品 / 财务）。
 * 后端为 Laravel Blade 服务端路由，字段以控制器返回结构为准声明契约。
 */
import { request } from "./request";
import type { paths } from "./schema";

// ===== Admin Products =====

/** 商品列表条目（status: 0 下架 / 1 上架 / 2 强制下架；type: 1 技能 / 2 商品；nature: physical 实物 / virtual 虚拟）。 */
export interface AdminProductItem {
  id: number;
  name: string;
  description: string | null;
  cover: string | null;
  price: number;
  type: number;
  nature: "physical" | "virtual";
  quota: number;
  skill_id: number | null;
  status: number;
  created_at: string | null;
}

/** 新建 / 编辑商品的提交载荷（价格与额度单位：分）。 */
export interface ProductPayload {
  name: string;
  description?: string;
  price: number;
  type: number;
  nature?: "physical" | "virtual";
  quota?: number;
  skill_id?: number;
  status?: number;
  cover?: string;
}

export interface AdminProductListResponse {
  list: AdminProductItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 商品列表（支持分页与状态过滤）。 */
export function getAdminProducts(
  params?: { page?: number; status?: number; type?: number; keyword?: string },
): Promise<AdminProductListResponse> {
  return request<AdminProductListResponse>("/v1/admin/products", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 商品上架 / 下架切换。 */
export function toggleProductStatus(id: number): Promise<unknown> {
  return request<unknown>(`/v1/admin/products/${id}/status`, {
    method: "PATCH",
    auth: true,
  });
}

/** 新建商品。 */
export function createAdminProduct(payload: ProductPayload): Promise<AdminProductItem> {
  return request<AdminProductItem>("/v1/admin/products", {
    method: "POST",
    body: payload,
    auth: true,
  });
}

/** 更新商品。 */
export function updateAdminProduct(id: number, payload: ProductPayload): Promise<AdminProductItem> {
  return request<AdminProductItem>(`/v1/admin/products/${id}`, {
    method: "PUT",
    body: payload,
    auth: true,
  });
}

/** 强制下架（管理员特权操作）。 */
export function forceArchiveProduct(id: number): Promise<unknown> {
  return request<unknown>(`/v1/admin/products/${id}/force-archive`, {
    method: "POST",
    auth: true,
  });
}

// ===== Admin Finance =====

/** 充值流水条目。 */
export interface AdminRechargeItem {
  id: number;
  out_trade_no: string | null;
  user_name: string | null;
  user_email: string | null;
  amount: number;
  type: string;
  created_at: string | null;
}

export interface AdminRechargeListResponse {
  list: AdminRechargeItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 充值流水列表。 */
export function getAdminFinanceRecharges(
  params?: { page?: number },
): Promise<AdminRechargeListResponse> {
  return request<AdminRechargeListResponse>("/v1/admin/finance/recharges", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 提现审批条目。 */
export interface AdminWithdrawalItem {
  id: number;
  user_name: string | null;
  user_email: string | null;
  amount: number;
  status: string;
  audited_at: string | null;
  created_at: string | null;
}

export interface AdminWithdrawalListResponse {
  list: AdminWithdrawalItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 提现单列表。 */
export function getAdminFinanceWithdrawals(
  params?: { page?: number },
): Promise<AdminWithdrawalListResponse> {
  return request<AdminWithdrawalListResponse>("/v1/admin/finance/withdrawals", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 提现审批通过（第一步：核对通过）。 */
export function approveWithdrawal(id: number): Promise<unknown> {
  return request<unknown>(`/v1/admin/finance/withdrawals/${id}/approve`, {
    method: "POST",
    auth: true,
  });
}

/** 提现驳回（冻结款退回可用余额）。 */
export function rejectWithdrawal(id: number, reason: string): Promise<unknown> {
  return request<unknown>(`/v1/admin/finance/withdrawals/${id}/reject`, {
    method: "POST",
    body: { reject_reason: reason },
    auth: true,
  });
}

// ===== Admin AI Providers =====

/** AI 供应商条目（api_key 脱敏输出，绝不返回完整 key）。 */
export interface AdminAiProviderItem {
  id: number;
  name: string;
  display_name: string | null;
  driver: string;
  model_name: string;
  base_url: string | null;
  masked_key: string;
  models_count: number;
  enabled: boolean;
  created_at: string | null;
}

export interface AdminAiProviderListResponse {
  list: AdminAiProviderItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** AI 供应商更新载荷（api_key 留空表示保留原值）。 */
export interface AiProviderPayload {
  base_url?: string;
  api_key?: string;
  enabled?: boolean;
}

/** AI 供应商更新结果（仅含脱敏 key）。 */
export interface AiProviderUpdateResult {
  id: number;
  base_url: string | null;
  masked_key: string;
  enabled: boolean;
}

/** AI 供应商列表。 */
export function getAdminAiProviders(
  params?: { page?: number },
): Promise<AdminAiProviderListResponse> {
  return request<AdminAiProviderListResponse>("/v1/admin/ai-providers", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 更新 AI 供应商（api_key 留空保留原值）。 */
export function updateAdminAiProvider(
  id: number,
  payload: AiProviderPayload,
): Promise<AiProviderUpdateResult> {
  return request<AiProviderUpdateResult>(`/v1/admin/ai-providers/${id}`, {
    method: "PATCH",
    body: payload,
    auth: true,
  });
}

// ===== Admin Skills =====

/** 技能条目（api_key 脱敏输出）。 */
export interface AdminSkillItem {
  id: number;
  product_id: number;
  product_name: string | null;
  endpoint: string | null;
  method: string | null;
  pricing_type: number;
  masked_api_key: string;
  free_quota: number | null;
  product_status: number;
  calls_count: number;
}

export interface AdminSkillListResponse {
  list: AdminSkillItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 技能上下架切换结果。 */
export interface SkillToggleResult {
  id: number;
  product_id: number;
  status: number;
}

/** 技能列表。 */
export function getAdminSkills(
  params?: { page?: number },
): Promise<AdminSkillListResponse> {
  return request<AdminSkillListResponse>("/v1/admin/skills", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 技能上下架切换。 */
export function toggleAdminSkillStatus(id: number): Promise<SkillToggleResult> {
  return request<SkillToggleResult>(`/v1/admin/skills/${id}/status`, {
    method: "PATCH",
    auth: true,
  });
}

/** 待审核技能条目。 */
export interface PendingSkillItem {
  id: number;
  product_id: number;
  product_name: string | null;
  endpoint: string | null;
  method: string | null;
  pricing_type: number;
  status: string;
  system_prompt: string | null;
  free_quota: number | null;
  created_at: string | null;
}

/** 待审核技能列表响应。 */
export interface PendingSkillListResponse {
  list: PendingSkillItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 待审核技能列表。 */
export function getAdminPendingSkills(
  params?: { page?: number },
): Promise<PendingSkillListResponse> {
  return request<PendingSkillListResponse>("/v1/admin/skills/pending", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 审核技能（通过/驳回）。 */
export function auditSkill(
  id: number,
  approve: boolean,
  note?: string,
): Promise<unknown> {
  return request<unknown>(`/v1/admin/skills/${id}/audit`, {
    method: "POST",
    body: { approve, note },
    auth: true,
  });
}

// ===== Admin API Keys =====

/** API Key 条目（key 脱敏输出：仅 key_prefix + ****）。 */
export interface AdminApiKeyItem {
  id: number;
  user_id: number;
  user_email: string | null;
  name: string;
  masked_key: string;
  status: number;
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string | null;
}

export interface AdminApiKeyListResponse {
  list: AdminApiKeyItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** API Key 列表。 */
export function getAdminApiKeys(
  params?: { page?: number },
): Promise<AdminApiKeyListResponse> {
  return request<AdminApiKeyListResponse>("/v1/admin/api-keys", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 吊销 API Key。 */
export function revokeAdminApiKey(
  id: number,
): Promise<{ id: number; status: number }> {
  return request<{ id: number; status: number }>(`/v1/admin/api-keys/${id}`, {
    method: "DELETE",
    auth: true,
  });
}

// ===== Admin API Calls =====

/** 调用流水条目（cost 单位：毫）。 */
export interface AdminApiCallItem {
  id: number;
  request_id: string;
  user_id: number;
  user_name: string | null;
  product_id: number;
  product_name: string | null;
  status_code: number;
  duration_ms: number;
  cost: number;
  created_at: string;
}

export interface AdminApiCallListResponse {
  list: AdminApiCallItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 调用流水列表（支持 user_id / product_id / start_date / end_date 筛选）。 */
export function getAdminApiCalls(
  params?: {
    page?: number;
    user_id?: number;
    product_id?: number;
    start_date?: string;
    end_date?: string;
  },
): Promise<AdminApiCallListResponse> {
  return request<AdminApiCallListResponse>("/v1/admin/api-calls", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

// ── Admin Orders ──────────────────────────────────────────────

/** 发货请求体类型（schema 仅有 tracking_no，实际已扩展 express_company）。 */
type ShipOrderReq = {
  tracking_no: string;
  express_company: string;
};

/** 后台订单条目（schema 无 200 响应定义，按 UI 使用字段声明）。 */
export interface AdminOrderItem {
  id: number;
  order_sn?: string;
  order_no?: string;
  user_name?: string;
  status?: string;
  tracking_no?: string;
  express_company?: string;
  total_amount?: number;
  amount?: number;
  product_name?: string;
  nature?: string;
  created_at?: string;
}

/** 后台订单列表响应。 */
export interface AdminOrderListResponse {
  list: AdminOrderItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 后台订单列表（支持分页）。 */
export function getAdminOrders(
  params?: { page?: number },
): Promise<AdminOrderListResponse> {
  return request<AdminOrderListResponse>("/v1/admin/orders", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 实物订单发货（tracking_no 幂等）。 */
export function shipAdminOrder(
  orderId: number,
  trackingNo: string,
  expressCompany: string,
): Promise<unknown> {
  const body: ShipOrderReq = { tracking_no: trackingNo, express_company: expressCompany };
  return request<unknown>(`/v1/admin/orders/${orderId}/ship`, {
    method: "POST",
    auth: true,
    body,
  });
}

/** 物流轨迹条目。 */
export interface TrackingItem {
  time: string;
  context: string;
}

/** 物流轨迹查询响应。 */
export interface TrackingInfo {
  order_no: string;
  tracking_no: string;
  express_company: string;
  express_company_name: string;
  shipped_at: string | null;
  tracks: TrackingItem[];
}

/** 查询订单物流轨迹。 */
export function getOrderTracking(orderId: number): Promise<TrackingInfo> {
  return request<TrackingInfo>(`/v1/admin/orders/${orderId}/tracking`, {
    method: "GET",
    auth: true,
  });
}

/** 钱包条目。 */
export interface AdminWalletItem {
  id: number;
  user_id: number;
  user_email: string | null;
  balance: number;
  frozen_balance: number;
  created_at: string | null;
}

/** 钱包列表响应。 */
export interface AdminWalletListResponse {
  list: AdminWalletItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 钱包交易记录条目。 */
export interface WalletTxItem {
  id: number;
  wallet_id: number;
  type: string;
  amount: number;
  description: string | null;
  out_trade_no: string | null;
  created_at: string | null;
}

/** 钱包交易记录响应。 */
export interface WalletTxListResponse {
  wallet: {
    id: number;
    user_id: number;
    balance: number;
    frozen_balance: number;
  };
  list: WalletTxItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 钱包列表。 */
export function getAdminWallets(
  params?: { page?: number },
): Promise<AdminWalletListResponse> {
  return request<AdminWalletListResponse>("/v1/admin/wallets", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 钱包交易记录。 */
export function getWalletTransactions(
  walletId: number,
  params?: { page?: number },
): Promise<WalletTxListResponse> {
  return request<WalletTxListResponse>(
    `/v1/admin/wallets/${walletId}/transactions`,
    {
      method: "GET",
      params: params as Record<string, unknown> | undefined,
      auth: true,
    },
  );
}

/** 账本账户条目。 */
export interface LedgerAccountItem {
  id: number;
  owner_type: string;
  owner_id: number;
  account_type: string;
  currency: string;
  balance: number;
  entries_count: number;
  created_at: string | null;
}

/** 账本账户列表响应。 */
export interface LedgerAccountListResponse {
  list: LedgerAccountItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 账本流水条目。 */
export interface LedgerEntryItem {
  id: number;
  transaction_id: string;
  account_id: number;
  direction: string;
  amount: number;
  biz_type: string;
  biz_id: number;
  created_at: string | null;
}

/** 账本流水列表响应。 */
export interface LedgerEntryListResponse {
  list: LedgerEntryItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 账本统计。 */
export interface LedgerStats {
  accounts: number;
  entries: number;
  total_balance: number;
}

/** 账本账户列表。 */
export function getAdminLedgerAccounts(
  params?: { page?: number },
): Promise<LedgerAccountListResponse> {
  return request<LedgerAccountListResponse>("/v1/admin/ledger/accounts", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** 账本流水分页。 */
export function getAdminLedgerEntries(
  accountId: number,
  params?: { page?: number },
): Promise<LedgerEntryListResponse> {
  return request<LedgerEntryListResponse>(
    `/v1/admin/ledger/accounts/${accountId}/entries`,
    {
      method: "GET",
      params: params as Record<string, unknown> | undefined,
      auth: true,
    },
  );
}

/** 账本统计。 */
export function getAdminLedgerStats(): Promise<LedgerStats> {
  return request<LedgerStats>("/v1/admin/ledger/stats", {
    method: "GET",
    auth: true,
  });
}

/** 系统健康状态。 */
export interface SystemHealth {
  db: boolean;
  cache: boolean;
  nginx: boolean;
  'php-fpm': boolean;
  worker: string;
}

/** 失败任务条目。 */
export interface FailedJobItem {
  id: number;
  uuid: string;
  queue: string;
  exception: string;
  failed_at: string;
}

/** 队列统计。 */
export interface QueueStats {
  pending: number;
  failed: FailedJobItem[];
}

/** 备份文件。 */
export interface BackupFile {
  name: string;
  size: number;
  mtime: string;
}

/** 系统健康检查。 */
export function getSystemHealth(): Promise<SystemHealth> {
  return request<SystemHealth>("/v1/admin/system/health", {
    method: "GET",
    auth: true,
  });
}

/** 队列统计。 */
export function getQueueStats(): Promise<QueueStats> {
  return request<QueueStats>("/v1/admin/system/queue", {
    method: "GET",
    auth: true,
  });
}

/** 重试失败任务。 */
export function retryFailedJob(uuid: string): Promise<unknown> {
  return request<unknown>("/v1/admin/system/queue/retry", {
    method: "POST",
    body: { uuid },
    auth: true,
  });
}

/** 备份列表。 */
export function getSystemBackups(): Promise<BackupFile[]> {
  return request<BackupFile[]>("/v1/admin/system/backups", {
    method: "GET",
    auth: true,
  });
}

/** 最近日志。 */
export function getSystemLogs(): Promise<string[]> {
  return request<string[]>("/v1/admin/system/logs", {
    method: "GET",
    auth: true,
  });
}

// ── Admin Users ───────────────────────────────────────────────

/** 后台用户条目（schema 无 200 响应定义，按 UI 使用字段声明）。 */
export interface AdminUserItem {
  id: number;
  name: string;
  email?: string;
  is_admin?: boolean | number;
  is_active?: boolean | number;
  created_at?: string;
}

/** 后台用户列表响应。 */
export interface AdminUserListResponse {
  list: AdminUserItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

/** 后台用户列表（支持分页）。 */
export function getAdminUsers(
  params?: { page?: number },
): Promise<AdminUserListResponse> {
  return request<AdminUserListResponse>("/v1/admin/users", {
    method: "GET",
    params: params as Record<string, unknown> | undefined,
    auth: true,
  });
}

/** Dashboard 聚合统计响应。 */
export interface AdminDashboardStats {
  total_users: number;
  today_orders: number;
  today_revenue: number;
  total_products: number;
  pending_shipment: number;
  paid_orders: number;
}

/** Dashboard 聚合统计。 */
export function getAdminDashboardStats(): Promise<AdminDashboardStats> {
  return request<AdminDashboardStats>("/v1/admin/dashboard/stats", {
    method: "GET",
    auth: true,
  });
}

/** 封禁/解封用户。 */
export function toggleUserBan(id: number): Promise<{ id: number; is_active: boolean }> {
  return request<{ id: number; is_active: boolean }>(`/v1/admin/users/${id}/ban`, {
    method: "PATCH",
    auth: true,
  });
}
