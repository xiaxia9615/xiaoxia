import { createFileRoute } from "@tanstack/react-router";
import { AdminShell } from "@/config/components/admin/AdminShell";

/** /admin 布局路由：左侧侧边栏 + 右侧子页面（Outlet）。 */
export const Route = createFileRoute("/admin")({
  component: AdminShell,
});
