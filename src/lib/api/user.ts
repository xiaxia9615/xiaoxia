import { JSEncrypt } from "jsencrypt";
import { request } from "./request";

/** 用户信息。 */
export interface User {
  id: number;
  username: string;
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
}

function mapUser(u: Record<string, unknown>): User {
  const nickname = String(u.nickname || u.username || "用户");
  const avatar = typeof u.avatar === "string" ? u.avatar : undefined;
  const id = typeof u.id === "number" ? u.id : Number(u.id);
  return {
    id: Number.isFinite(id) ? id : 0,
    username: String(u.username || ""),
    nickname,
    avatar,
    email: typeof u.email === "string" ? u.email : undefined,
    sex: typeof u.sex === "number" ? u.sex : undefined,
    birthday: typeof u.birthday === "string" ? u.birthday : undefined,
    mobile: typeof u.mobile === "string" ? u.mobile : undefined,
    province: typeof u.province === "string" ? u.province : undefined,
    city: typeof u.city === "string" ? u.city : undefined,
    district: typeof u.district === "string" ? u.district : undefined,
    integral: typeof u.integral === "number" ? u.integral : undefined,
  };
}

interface PublicKeyData {
  public_key: string;
  token: string;
  expires_in: number;
}

/** 获取登录 RSA 公钥与 rsa_token。 */
export async function fetchPublicKey(): Promise<PublicKeyData> {
  const data = await request<PublicKeyData>("/security/publickey");
  return data;
}

/** 用 jsencrypt 对明文做 RSA PKCS1 v1.5 加密（匹配后端 OPENSSL_PKCS1_PADDING），返回 base64。 */
function rsaEncrypt(plain: string, publicKeyPem: string): string {
  const enc = new JSEncrypt();
  enc.setPublicKey(publicKeyPem);
  const encrypted = enc.encrypt(plain);
  if (!encrypted) {
    throw new Error("密码加密失败");
  }
  return encrypted;
}

/**
 * 登录。密码用后端下发的 RSA 公钥加密后提交（PKCS1 v1.5，带 rsa_token），
 * 与注册/改密保持一致，避免明文密码经过任何前端日志或代理层。
 * 若公钥接口不可用则回退明文提交（后端历史行为）。
 */
export async function login(username: string, password: Promise<string> | string): Promise<{
  token: string;
  user: User;
}> {
  const plain = await password;
  let body: Record<string, unknown> = { username, password: plain };
  try {
    const pub = await fetchPublicKey();
    const enc = new JSEncrypt();
    enc.setPublicKey(pub.public_key);
    const encrypted = enc.encrypt(`${plain}|${pub.token}`);
    if (encrypted) {
      body = { username, password: encrypted, rsa_token: pub.token, encrypt: "rsa" };
    }
  } catch {
    // 公钥接口不可用：HTTPS 下回退明文，保持与旧后端兼容
  }
  const data = await request<{ token?: string; user?: Record<string, unknown> }>(
    "/login",
    { method: "POST", body },
  );
  const raw = data.user;
  const name = raw ? String(raw.name ?? raw.username ?? username) : username;
  const idNum = Number(raw?.id ?? 0);
  const user: User = {
    id: Number.isFinite(idNum) ? idNum : 0,
    username: name,
    nickname: name,
    email: raw && typeof raw.email === "string" ? raw.email : undefined,
  };
  return { token: String(data.token || ""), user };
}

/** 注册。对接官网真实接口 POST /api/register（明文账号密码）。 */
export async function register(username: string, password: string): Promise<User> {
  const data = await request<Record<string, unknown>>("/register", {
    method: "POST",
    body: { username, password },
  });
  const u = (data.user ?? data) as Record<string, unknown>;
  const name = String(u.name ?? u.username ?? username);
  const idNum = Number(u.id ?? 0);
  return {
    id: Number.isFinite(idNum) ? idNum : 0,
    username: name,
    nickname: name,
    email: typeof u.email === "string" ? u.email : undefined,
  };
}

/** 用户信息（需登录）。 */
export async function fetchProfile(): Promise<User> {
  const data = await request<Record<string, unknown>>("/user/profile", { auth: true });
  return mapUser(data);
}

/** 用户信息编辑（user/update），返回最新用户信息。 */
export async function updateProfile(
  input: Partial<{
    nickname: string;
    avatar: string;
    sex: number;
    birthday: string;
    province: string;
    city: string;
    district: string;
    email: string;
    mobile: string;
  }>,
): Promise<User> {
  const data = await request<Record<string, unknown>>("/user/update", {
    auth: true,
    method: "POST",
    body: input,
  });
  return mapUser(data);
}

/** 修改密码（user/password），密码 RSA 加密，成功后返回新 token。 */
export async function changePassword(
  oldPassword: string,
  newPassword: string,
): Promise<string> {
  const pub = await fetchPublicKey();
  const enc = (plain: string) => rsaEncrypt(`${plain}|${pub.token}`, pub.public_key);
  const data = await request<Record<string, unknown>>("/user/password", {
    auth: true,
    method: "POST",
    body: { old_pwd: enc(oldPassword), new_pwd: enc(newPassword), rsa_token: pub.token },
  });
  const token = data.token as string | undefined;
  return token ?? "";
}

/** 发送短信验证码。scene=find_password 用于密码找回。 */
export async function sendVerifyCode(
  mobile: string,
  scene: "find_password" = "find_password",
): Promise<void> {
  await request<Record<string, unknown>>("/verify/send", {
    method: "POST",
    body: { mobile, scene },
  });
}

/** 重置密码（手机号验证码找回，/user/reset-password）。新密码 RSA 加密后提交。 */
export async function resetPassword(input: {
  phone: string;
  code: string;
  newPassword: string;
}): Promise<void> {
  const pub = await fetchPublicKey();
  const newPwd = rsaEncrypt(`${input.newPassword}|${pub.token}`, pub.public_key);
  await request<Record<string, unknown>>("/user/reset-password", {
    method: "POST",
    body: {
      phone: input.phone,
      code: input.code,
      new_pwd: newPwd,
      rsa_token: pub.token,
    },
  });
}
