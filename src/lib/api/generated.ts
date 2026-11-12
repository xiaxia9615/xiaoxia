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
