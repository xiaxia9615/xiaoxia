import { useEffect, useState } from "react";
import { Outlet, Link, useLocation } from "@tanstack/react-router";
import { ChevronDown } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";

/** 侧边栏菜单项定义。 */
interface AdminMenuItem {
  to: string;
  label: string;
}

/** 侧边栏菜单分组定义。 */
interface AdminMenuGroup {
  key: string;
  label: string;
  items: AdminMenuItem[];
}

/** 侧边栏菜单：5 组折叠（概览 / 交易 / 资产 / AI / 系统）。 */
const ADMIN_MENU_GROUPS: AdminMenuGroup[] = [
  {
    key: "overview",
    label: "概览",
    items: [{ to: "/admin", label: "仪表盘" }],
  },
  {
    key: "trade",
    label: "交易",
    items: [
      { to: "/admin/orders", label: "订单" },
      { to: "/admin/products", label: "商品" },
      { to: "/admin/categories", label: "分类" },
      { to: "/admin/finance", label: "财务中心" },
    ],
  },
  {
    key: "assets",
    label: "资产",
    items: [
      { to: "/admin/users", label: "用户" },
      { to: "/admin/wallets", label: "钱包" },
      { to: "/admin/ledger", label: "Ledger" },
    ],
  },
  {
    key: "ai",
    label: "AI",
    items: [
      { to: "/admin/ai-providers", label: "AI 供应商" },
      { to: "/admin/skills", label: "技能" },
      { to: "/admin/skill-reviews", label: "技能审核" },
    ],
  },
  {
    key: "system",
    label: "系统",
    items: [
      { to: "/admin/api-keys", label: "API Keys" },
      { to: "/admin/api-calls", label: "调用流水" },
      { to: "/admin/queue-monitor", label: "队列监控" },
      { to: "/admin/system", label: "系统" },
    ],
  },
];

/** 判断菜单项是否激活：/admin 精确匹配，其余前缀匹配。 */
function isActivePath(pathname: string, to: string): boolean {
  return to === "/admin" ? pathname === "/admin" : pathname.startsWith(to);
}

/** Admin 后台外壳：左侧分组折叠侧边栏 + 顶部面包屑 + 右侧主体区（Outlet）。 */
export function AdminShell() {
  const { pathname } = useLocation();

  // 当前激活的菜单项与所属分组（用于面包屑与自动展开）
  const activeItem = ADMIN_MENU_GROUPS.flatMap((group) => group.items).find((item) =>
    isActivePath(pathname, item.to),
  );
  const activeGroupKey =
    ADMIN_MENU_GROUPS.find((group) =>
      group.items.some((item) => isActivePath(pathname, item.to)),
    )?.key ?? "overview";

  const [openGroups, setOpenGroups] = useState<Record<string, boolean>>(() =>
    Object.fromEntries(ADMIN_MENU_GROUPS.map((group) => [group.key, group.key === activeGroupKey])),
  );

  // 路由切换时自动展开当前分组
  useEffect(() => {
    setOpenGroups((prev) => ({ ...prev, [activeGroupKey]: true }));
  }, [activeGroupKey]);

  return (
    <div className="flex min-h-screen bg-gray-50">
      <aside className="sticky top-0 hidden h-screen w-56 shrink-0 border-r border-zinc-800 bg-[#0a0a0a] md:flex md:flex-col">
        <div className="flex h-14 items-center border-b border-zinc-800 px-5">
          <span className="font-mono text-sm font-semibold tracking-tight text-emerald-500">
            Admin Console
          </span>
        </div>
        <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-4">
          {ADMIN_MENU_GROUPS.map((group) => (
            <Collapsible
              key={group.key}
              open={openGroups[group.key] ?? false}
              onOpenChange={(open) =>
                setOpenGroups((prev) => ({ ...prev, [group.key]: open }))
              }
            >
              <CollapsibleTrigger className="flex w-full items-center justify-between rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 transition-colors hover:bg-zinc-800 hover:text-zinc-300">
                {group.label}
                <ChevronDown
                  className={cn(
                    "h-3.5 w-3.5 transition-transform",
                    openGroups[group.key] ? "rotate-180" : "",
                  )}
                />
              </CollapsibleTrigger>
              <CollapsibleContent>
                <div className="mt-0.5 space-y-0.5">
                  {group.items.map((item) => {
                    const active = isActivePath(pathname, item.to);
                    return (
                      <Link
                        key={item.to}
                        to={item.to}
                        className={cn(
                          "block rounded-md border-l-2 px-3 py-2 text-sm transition-colors",
                          active
                            ? "border-emerald-500 bg-zinc-800 font-medium text-emerald-500"
                            : "border-transparent text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100",
                        )}
                      >
                        {item.label}
                      </Link>
                    );
                  })}
                </div>
              </CollapsibleContent>
            </Collapsible>
          ))}
        </nav>
      </aside>
      <div className="min-w-0 flex-1">
        <header className="sticky top-0 z-10 flex h-14 items-center border-b border-gray-200 bg-white/80 px-6 backdrop-blur">
          <Breadcrumb>
            <BreadcrumbList>
              <BreadcrumbItem>
                <BreadcrumbLink asChild>
                  <Link to="/admin" className="text-gray-500 hover:text-gray-900">
                    Admin
                  </Link>
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator />
              <BreadcrumbItem>
                <BreadcrumbPage className="text-gray-900">
                  {activeItem?.label ?? "仪表盘"}
                </BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
        </header>
        <Outlet />
      </div>
    </div>
  );
}
