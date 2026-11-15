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
