import { request, TOKEN_KEY, UID_KEY } from "./request";
import type { paths } from "./schema";

/** 用户信息。 */
export interface User {
  id: number;
  name: string;
  nickname: string;
  avatar?: string;
  email?: string;
  sex?: number;
  birthday?: string;
  mobile?: string;
  province?: string;
  city?: string;
  district?: string;
  integral?: number;
  is_admin?: boolean;
  is_finance?: boolean;
  is_active?: boolean;
  uid?: string;
}

function mapUser(u: Record<string, unknown>): User {
  const name = String(u.name || u.nickname || "用户");
  const avatar = typeof u.avatar === "string" ? u.avatar : undefined;
  const id = typeof u.id === "number" ? u.id : Number(u.id);
  return {
    id: Number.isFinite(id) ? id : 0,
    name,
    nickname: name,
    avatar,
    email: typeof u.email === "string" ? u.email : undefined,
    sex: typeof u.sex === "number" ? u.sex : undefined,
    birthday: typeof u.birthday === "string" ? u.birthday : undefined,
    mobile: typeof u.mobile === "string" ? u.mobile : undefined,
    province: typeof u.province === "string" ? u.province : undefined,
    city: typeof u.city === "string" ? u.city : undefined,
    district: typeof u.district === "string" ? u.district : undefined,
    integral: typeof u.integral === "number" ? u.integral : undefined,
    is_admin: typeof u.is_admin === "boolean" ? u.is_admin : false,
    is_finance: typeof u.is_finance === "boolean" ? u.is_finance : false,
    is_active: typeof u.is_active === "boolean" ? u.is_active : true,
    uid: typeof u.uid === "string" ? u.uid : String(u.id ?? 0),
  };
}

/** Schema 请求体类型（auth 端点 responses: never，响应类型暂保留手写，见待汇报清单） */
type LoginReq = paths["/api/v1/auth/login"]["post"]["requestBody"]["content"]["application/json"];
type RegisterReq = paths["/api/v1/auth/register"]["post"]["requestBody"]["content"]["application/json"];
type SmsSendReq = paths["/api/v1/auth/sms/send"]["post"]["requestBody"]["content"]["application/json"];
type SmsLoginReq = paths["/api/v1/auth/sms/login"]["post"]["requestBody"]["content"]["application/json"];

/**
 * 登录。密码明文提交（HTTPS 已加密传输层）。
 * 对接后端 POST /api/v1/auth/login，字段 email + password。
 */
export async function login(email: string, password: string): Promise<{
  token: string;
  user: User;
}> {
  const body: LoginReq = { email, password };
  const data = await request<{ token?: string; user?: Record<string, unknown> }>(
    "/login",
    { method: "POST", body },
  );
  const raw = data.user;
  const name = raw ? String(raw.name ?? "用户") : "用户";
  const idNum = Number(raw?.id ?? 0);
  const token = data.token ?? "";
  const uid = raw && typeof raw.uid === "string" ? raw.uid : String(raw?.id ?? 0);
  if (token) localStorage.setItem(TOKEN_KEY, token);
  if (uid && uid !== "0") localStorage.setItem(UID_KEY, uid);
  return {
    token,
    user: {
      id: Number.isFinite(idNum) ? idNum : 0,
      name,
      nickname: name,
      email: raw && typeof raw.email === "string" ? raw.email : undefined,
      is_admin: raw && typeof raw.is_admin === "boolean" ? raw.is_admin : false,
      is_finance: raw && typeof raw.is_finance === "boolean" ? raw.is_finance : false,
      is_active: raw && typeof raw.is_active === "boolean" ? raw.is_active : true,
      uid,
    },
  };
}

/** 注册。对接后端 POST /api/v1/auth/register，字段 name + email + password。 */
export async function register(name: string, email: string, password: string): Promise<User> {
  const body: RegisterReq = { name, email, password };
  const data = await request<Record<string, unknown>>("/register", {
    method: "POST",
    body,
  });
  const u = (data.user ?? data) as Record<string, unknown>;
  const userName = String(u.name ?? name);
  const idNum = Number(u.id ?? 0);
  return {
    id: Number.isFinite(idNum) ? idNum : 0,
    name: userName,
    nickname: userName,
    email: typeof u.email === "string" ? u.email : undefined,
    is_admin: typeof u.is_admin === "boolean" ? u.is_admin : false,
    is_finance: typeof u.is_finance === "boolean" ? u.is_finance : false,
    is_active: typeof u.is_active === "boolean" ? u.is_active : true,
    uid: typeof u.uid === "string" ? u.uid : String(u.id ?? 0),
  };
}

/** 用户信息（需登录）。调用 GET /api/v1/auth/me。 */
export async function fetchProfile(): Promise<User> {
  const data = await request<Record<string, unknown>>("/v1/auth/me", { auth: true });
  return mapUser(data);
}

/** 发送短信验证码（手机号登录）。调用 POST /api/v1/auth/sms/send。 */
export async function smsSend(phone: string): Promise<void> {
  const body: SmsSendReq = { phone };
  await request<Record<string, unknown>>("/v1/auth/sms/send", {
    method: "POST",
    body,
  });
}

/** 手机号验证码登录（不存在则自动注册）。调用 POST /api/v1/auth/sms/login。 */
export async function smsLogin(phone: string, code: string): Promise<{
  token: string;
  user: User;
}> {
  const body: SmsLoginReq = { phone, code };
  const data = await request<{ token?: string; user?: Record<string, unknown> }>(
    "/v1/auth/sms/login",
    { method: "POST", body },
  );
  const raw = data.user;
  const name = raw ? String(raw.name ?? "用户") : "用户";
  const idNum = Number(raw?.id ?? 0);
  const token = data.token ?? "";
  const uid = raw && typeof raw.uid === "string" ? raw.uid : String(raw?.id ?? 0);
  if (token) localStorage.setItem(TOKEN_KEY, token);
  if (uid && uid !== "0") localStorage.setItem(UID_KEY, uid);
  return {
    token,
    user: {
      id: Number.isFinite(idNum) ? idNum : 0,
      name,
      nickname: name,
      mobile: phone,
      is_admin: raw && typeof raw.is_admin === "boolean" ? raw.is_admin : false,
      is_finance: raw && typeof raw.is_finance === "boolean" ? raw.is_finance : false,
      is_active: raw && typeof raw.is_active === "boolean" ? raw.is_active : true,
      uid,
    },
  };
}
/** 获取用户详细资料（含 phone/avatar/开发者状态）。调用 GET /api/v1/user/profile。 */
export async function fetchUserProfile(): Promise<User> {
  const data = await request<Record<string, unknown>>("/v1/user/profile", { auth: true });
  return mapUser(data);
}

/** 修改用户资料（name/phone/avatar）。调用 POST /api/v1/user/update。 */
export async function updateUserProfile(payload: {
  name?: string;
  phone?: string | null;
  avatar?: string | null;
}): Promise<{ id: number; name: string; phone: string | null; avatar: string | null }> {
  const data = await request<Record<string, unknown>>("/v1/user/update", {
    method: "POST",
    body: payload,
    auth: true,
  });
  return {
    id: Number(data.id ?? 0),
    name: String(data.name ?? ""),
    phone: typeof data.phone === "string" ? data.phone : null,
    avatar: typeof data.avatar === "string" ? data.avatar : null,
  };
}

/** 修改密码（需验证旧密码）。调用 POST /api/v1/user/password。 */
export async function changePassword(
  currentPassword: string,
  newPassword: string,
): Promise<void> {
  await request<Record<string, unknown>>("/v1/user/password", {
    method: "POST",
    body: { current_password: currentPassword, new_password: newPassword },
    auth: true,
  });
}

/** 重置密码（邮箱+验证码）。调用 POST /api/v1/user/reset-password。 */
export async function resetPassword(
  email: string,
  code: string,
  newPassword: string,
): Promise<void> {
  await request<Record<string, unknown>>("/v1/user/reset-password", {
    method: "POST",
    body: { email, code, new_password: newPassword },
    auth: false,
  });
}

/** 用户积分/余额概览。调用 GET /api/v1/user/integral。 */
export async function fetchIntegral(): Promise<{
  balance: number;
  frozen_balance: number;
  points: number;
}> {
  const data = await request<Record<string, unknown>>("/v1/user/integral", { auth: true });
  return {
    balance: typeof data.balance === "number" ? data.balance : 0,
    frozen_balance: typeof data.frozen_balance === "number" ? data.frozen_balance : 0,
    points: typeof data.points === "number" ? data.points : 0,
  };
}

export const AUTH_FEATURES = { profileEdit: true, changePassword: true, resetPassword: true } as const;
