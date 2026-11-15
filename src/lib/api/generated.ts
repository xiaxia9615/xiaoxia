/**
 * 由 OpenAPI 契约生成的 API 请求代码。
 * 基于 Scribe 生成的 openapi.yaml，复用项目现有 request 函数确保自动带 Authorization。
 */
import { customFetch } from "./orval-mutator";

// ===== Admin Orders =====

export interface AdminOrderItem {
  id: number;
  order_no: string;
  amount: number;
  status: string;
  /** 商品性质：physical 实物 / virtual 虚拟（历史订单可能为 null）。 */
  nature: "physical" | "virtual" | null;
  /** 物流单号（实物订单发货后填写）。 */
  tracking_no: string | null;
  product_name: string | null;
  user_name: string | null;
  user_email: string | null;
  created_at: string | null;
}

export interface AdminOrdersResponse {
  list: AdminOrderItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

export const getAdminOrders = (params?: { page?: number }): Promise<AdminOrdersResponse> => {
  return customFetch<AdminOrdersResponse>({
    url: "/admin/orders",
    method: "GET",
    params: params as Record<string, unknown> | undefined,
  });
};

/** 实物订单发货结果。 */
export interface AdminShipResult {
  id: number;
  order_no: string;
  tracking_no: string | null;
  shipped_at: string | null;
  status: string;
}

/** 实物订单发货（填写物流单号，仅待发货订单可发货）。 */
export const shipAdminOrder = (id: number, trackingNo: string): Promise<AdminShipResult> => {
  return customFetch<AdminShipResult>({
    url: `/admin/orders/${id}/ship`,
    method: "POST",
    data: { tracking_no: trackingNo },
  });
};

// ===== Admin Users =====

export interface AdminUserItem {
  id: number;
  name: string;
  email: string;
  is_admin: boolean;
  is_active: boolean;
  created_at: string | null;
}

export interface AdminUsersResponse {
  list: AdminUserItem[];
  total: number;
  current_page: number;
  last_page: number;
  per_page: number;
}

export const getAdminUsers = (params?: { page?: number }): Promise<AdminUsersResponse> => {
  return customFetch<AdminUsersResponse>({
    url: "/admin/users",
    method: "GET",
    params: params as Record<string, unknown> | undefined,
  });
};
