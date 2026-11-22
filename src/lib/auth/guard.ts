import { redirect } from "@tanstack/react-router";
import { TOKEN_KEY } from "@/lib/api/request";

const USER_KEY = "xiaoxia.user";

/**
 * 通用路由守卫：检查 localStorage token，无 token 则跳转登录页。
 * 用于所有需要登录的路由的 beforeLoad 钩子。
 */
export function requireAuth({
  location,
}: {
  location: { href: string };
}) {
  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) {
    throw redirect({
      to: "/login",
      search: { redirect: location.href },
    });
  }
}

/**
 * 管理员路由守卫：先校验登录，再校验 xiaoxia.user 的 is_admin。
 * 未登录或非管理员一律跳转登录页（不区分提示，避免暴露后台存在性）。
 */
export function requireAdmin({
  location,
}: {
  location: { href: string };
}) {
  requireAuth({ location });

  let isAdmin = false;
  try {
    const raw = localStorage.getItem(USER_KEY);
    if (raw) {
      const user = JSON.parse(raw) as { is_admin?: unknown };
      isAdmin = user.is_admin === true;
    }
  } catch {
    isAdmin = false;
  }

  if (!isAdmin) {
    throw redirect({
      to: "/login",
      search: { redirect: location.href },
    });
  }
}

/**
 * 访客路由守卫：已登录用户访问 /login 时按身份跳转。
 * is_admin → /admin，其他已登录用户 → /，只跳一次，避免与 requireAdmin 形成循环。
 */
export function requireGuest() {
  const token = localStorage.getItem(TOKEN_KEY);
  if (!token) {
    return;
  }

  let target = "/";
  try {
    const raw = localStorage.getItem(USER_KEY);
    if (raw) {
      const user = JSON.parse(raw) as { is_admin?: unknown };
      if (user.is_admin === true) {
        target = "/admin";
      }
    }
  } catch {
    // 用户信息损坏时按普通用户处理，跳首页
  }

  throw redirect({ to: target });
}
