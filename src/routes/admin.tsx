import { createFileRoute } from "@tanstack/react-router";
import { AdminShell } from "@/config/components/admin/AdminShell";
import { requireAuth } from "@/lib/auth/guard";

/** /admin 布局路由：beforeLoad 守卫拦截未登录访问，无 token 则跳转登录页。 */
export const Route = createFileRoute("/admin")({
  beforeLoad: requireAuth,
  component: AdminShell,
});
