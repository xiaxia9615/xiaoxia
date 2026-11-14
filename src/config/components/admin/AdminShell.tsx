import { Outlet, Link, useLocation } from "@tanstack/react-router";
import { cn } from "@/lib/utils";

/** 侧边栏菜单项定义。 */
interface AdminMenuItem {
  to: string;
  label: string;
}

const ADMIN_MENU: AdminMenuItem[] = [
  { to: "/admin", label: "仪表盘" },
  { to: "/admin/users", label: "用户" },
  { to: "/admin/orders", label: "订单" },
  { to: "/admin/products", label: "商品" },
  { to: "/admin/ledger", label: "Ledger" },
  { to: "/admin/ai-providers", label: "AI 供应商" },
  { to: "/admin/skills", label: "技能" },
  { to: "/admin/skill-reviews", label: "技能审核" },
  { to: "/admin/api-calls", label: "调用流水" },
  { to: "/admin/api-keys", label: "API Keys" },
  { to: "/admin/wallets", label: "钱包" },
  { to: "/admin/system", label: "系统" },
  { to: "/admin/queue-monitor", label: "队列监控" },
  { to: "/admin/finance", label: "财务中心" },
];

/** Admin 后台外壳：左侧固定侧边栏 + 右侧主体区（Outlet）。 */
export function AdminShell() {
  const location = useLocation();
  return (
    <div className="flex min-h-screen bg-gray-50">
      <aside className="sticky top-0 hidden h-screen w-56 shrink-0 border-r border-gray-200 bg-white md:flex md:flex-col">
        <div className="flex h-14 items-center border-b border-gray-200 px-5">
          <span className="font-mono text-sm font-semibold tracking-tight text-gray-900">
            Admin Console
          </span>
        </div>
        <nav className="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
          {ADMIN_MENU.map((item) => {
            const active =
              item.to === "/admin"
                ? location.pathname === "/admin"
                : location.pathname.startsWith(item.to);
            return (
              <Link
                key={item.to}
                to={item.to}
                className={cn(
                  "block rounded-md px-3 py-2 text-sm transition-colors",
                  active
                    ? "bg-gray-100 font-medium text-gray-900"
                    : "text-gray-600 hover:bg-gray-50 hover:text-gray-900",
                )}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>
      </aside>
      <div className="min-w-0 flex-1">
        <Outlet />
      </div>
    </div>
  );
}
