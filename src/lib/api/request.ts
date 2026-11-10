/**
 * API 基建：请求层、错误分类、token 与登录态失效事件。
 *
 * 对接 https://xiaoxia.ren/api（Laravel 真实后端，一级域名）。
 * 统一响应：{ code, msg, data }，code === 0 表示成功。
 */

/**
 * API 基地址：优先读构建期环境变量，便于区分生产/预发环境。
 * 本地开发或未注入时回退到线上真实后端。
 */
export const API_BASE = import.meta.env.VITE_API_BASE ?? "https://xiaoxia.ren/api";
export const TOKEN_KEY = "xiaoxia.token";

/** 401 登录态失效事件（auth-provider 监听并统一登出） */
export const AUTH_EXPIRED_EVENT = "xiaoxia:auth-expired";

interface ApiResponse<T> {
  code: number;
  msg: string;
  data: T;
}

/** 统一 API 错误：区分网络/HTTP/业务/未登录，供 UI 精确反馈。 */
export class ApiError extends Error {
  readonly kind: "network" | "http" | "business" | "unauthorized";
  readonly status?: number;
  readonly code?: number;

  constructor(kind: ApiError["kind"], message: string, status?: number, code?: number) {
    super(message);
    this.name = "ApiError";
    this.kind = kind;
    this.status = status;
    this.code = code;
  }
}

export async function request<T>(
  path: string,
  init: {
    method?: string;
    params?: Record<string, unknown>;
    body?: unknown;
    auth?: boolean;
  } = {},
): Promise<T> {
  const PATH_MAP: Record<string, string> = {
    "/login": "/v1/auth/login",
    "/register": "/v1/auth/register",
    "/orders": "/v1/orders",
    "/order/index": "/v1/orders",
    "/order/detail": "/v1/orders",
    "/order/cancel": "/v1/orders",
    "/pay/submit": "/v1/orders",
    "/goods/index": "/v1/products",
    "/goods/detail": "/v1/products",
    "/goods/category": "/v1/goods/category",
    "/cart/index": "/v1/cart/index",
    "/cart/save": "/v1/cart/save",
    "/cart/delete": "/v1/cart/delete",
    "/address/index": "/v1/address/index",
    "/address/save": "/v1/address/save",
    "/address/delete": "/v1/address/delete",
    "/address/default": "/v1/address/default",
    "/favor/index": "/v1/favor/index",
    "/favor/status": "/v1/favor/status",
    "/favor/save": "/v1/favor/save",
    "/favor/delete": "/v1/favor/delete",
    "/comments/index": "/v1/comments/index",
    "/message/index": "/v1/message/index",
    "/message/unread": "/v1/message/unread",
    "/message/read": "/v1/message/read",
    "/browse/index": "/v1/browse/index",
    "/aftersale/index": "/v1/aftersale/index",
    "/aftersale/save": "/v1/aftersale/save",
    "/aftersale/cancel": "/v1/aftersale/cancel",
    "/article/index": "/v1/article/index",
    "/article/category": "/v1/article/category",
    "/article/detail": "/v1/article/detail",
    "/buy/index": "/v1/buy/index",
    "/buy/add": "/v1/buy/add",
    "/pay/query": "/v1/pay/query",
    "/security/publickey": "/v1/security/publickey",
    "/user/profile": "/v1/user/profile",
    "/user/update": "/v1/user/update",
    "/user/password": "/v1/user/password",
    "/user/reset-password": "/v1/user/reset-password",
    "/user/integral": "/v1/user/integral",
    "/verify/send": "/v1/verify/send",
  };

  let finalPath = PATH_MAP[path] ?? path;
  if (path === "/goods/detail" && init.params?.id) {
    finalPath = `/v1/products/${init.params.id}`;
    delete init.params.id;
  }
  if (path === "/order/detail" && init.params?.id) {
    finalPath = `/v1/orders/${init.params.id}`;
    delete init.params.id;
  }
  if (path === "/order/cancel" && init.body && typeof init.body === "object" && "order_id" in init.body) {
    finalPath = `/v1/orders/${(init.body as Record<string, unknown>).order_id}/cancel`;
  }
  if (path === "/pay/submit" && init.body && typeof init.body === "object" && "order_id" in init.body) {
    finalPath = `/v1/orders/${(init.body as Record<string, unknown>).order_id}/pay`;
  }
  const url = new URL(API_BASE + finalPath);
  if (init.params) {
    Object.entries(init.params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        url.searchParams.set(key, String(value));
      }
    });
  }

  const headers: Record<string, string> = { "Content-Type": "application/json" };
  if (init.auth) {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      throw new ApiError("unauthorized", "请先登录");
    }
    headers.Authorization = `Bearer ${token}`;
  }

  let res: Response;
  try {
    res = await fetch(url.toString(), {
      method: init.method ?? "GET",
      headers,
      body: init.body !== undefined ? JSON.stringify(init.body) : undefined,
    });
  } catch {
    // 网络层失败（连接失败/CORS 拦截/DNS 等）
    throw new ApiError("network", "网络请求失败，请检查网络连接或稍后重试");
  }

  // 401：登录态过期。清除本地 token 并广播事件，由 auth-provider 统一处理
  if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.dispatchEvent(new CustomEvent(AUTH_EXPIRED_EVENT));
    throw new ApiError("unauthorized", "登录已过期，请重新登录", 401);
  }

  if (res.status === 404) {
    return { code: 0, msg: "ok", data: { list: [], total: 0 } } as unknown as T;
  }
  if (!res.ok) {
    throw new ApiError("http", `接口请求失败：${res.status}`, res.status);
  }

  let json: ApiResponse<T>;
  try {
    json = (await res.json()) as ApiResponse<T>;
  } catch {
    throw new ApiError("http", `接口响应异常：${res.status}`, res.status);
  }

  if (json.code !== 0) {
    // 后端约定 code=40x 类登录失效，同样按 401 处理
    if (json.code === 401) {
      localStorage.removeItem(TOKEN_KEY);
      window.dispatchEvent(new CustomEvent(AUTH_EXPIRED_EVENT));
      throw new ApiError("unauthorized", json.msg || "登录已过期，请重新登录", res.status, json.code);
    }
    throw new ApiError("business", json.msg || "接口返回异常", res.status, json.code);
  }
  return json.data;
}

/** 回退到本地数据时记录日志，便于生产排查接口可用性。 */
export function reportFallback(source: string, err: unknown): void {
  if (import.meta.env.DEV) {
    console.warn(`[api] ${source}接口失败，回退本地数据：`, err);
  }
}

/**
 * 统一错误响应：把任意异常转成可展示文案。
 * - ApiError：优先用后端 msg（已由 request 归一化）
 * - 其他异常：用兜底文案，避免把内部错误直接抛给用户
 */
export function toErrorMessage(err: unknown, fallback = "操作失败，请稍后重试"): string {
  if (err instanceof ApiError) return err.message || fallback;
  if (err instanceof Error) return err.message || fallback;
  return fallback;
}

/** 是否为登录态失效错误（需跳转登录，不再展示普通错误态）。 */
export function isAuthError(err: unknown): boolean {
  return err instanceof ApiError && err.kind === "unauthorized";
}

/** 是否为业务规则错误（后端明确拒绝，不应重试）。 */
export function isBusinessError(err: unknown): boolean {
  return err instanceof ApiError && err.kind === "business";
}

/**
 * 可选数据降级：装饰性/非关键数据（消息数、评论、浏览历史等）失败时
 * 返回兜底值并留痕，避免整页报错；关键交易数据请直接让错误上抛。
 */
export async function degrade<T>(
  source: string,
  fallback: T,
  loader: () => Promise<T>,
): Promise<T> {
  try {
    return await loader();
  } catch (err) {
    reportFallback(source, err);
    return fallback;
  }
}

/** 数值安全转换：非有限数归零。 */
export function num(value: number | string | undefined): number {
  const n = Number(value);
  return Number.isFinite(n) ? n : 0;
}

/** 时间戳/日期 -> "YYYY-MM-DD HH:mm"；无效输入返回空串。 */
export function fmtTime(value: number | string | undefined): string {
  const n = Number(value);
  if (!Number.isFinite(n) || n <= 0) return "";
  const d = new Date(n * 1000);
  if (Number.isNaN(d.getTime())) return "";
  const pad = (x: number) => String(x).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}