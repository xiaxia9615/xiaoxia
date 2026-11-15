/**
 * Admin 后台 API 请求层（商品 / 财务）。
 * 后端为 Laravel Blade 服务端路由，字段以控制器返回结构为准声明契约。
 */
import { request } from "./request";

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
  return request<AdminProductListResponse>("/admin/products", {
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
  return request<AdminRechargeListResponse>("/admin/finance/recharges", {
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
  return request<AdminWithdrawalListResponse>("/admin/finance/withdrawals", {
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
