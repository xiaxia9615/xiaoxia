import { redirect } from "@tanstack/react-router";
import { TOKEN_KEY } from "@/lib/api/request";

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
