import { useEffect, useRef } from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
  Link,
  Outlet,
  createRootRouteWithContext,
  redirect,
  useRouterState,
} from "@tanstack/react-router";
import { AuthProvider } from "@/features/account/auth-provider";
import { TOKEN_KEY } from "@/lib/api";
import { CartProvider } from "@/features/cart/cart-provider";
import { Toaster } from "@/components/ui/sonner";
import { reportError, trackPageView } from "@/lib/observability";

function NotFoundComponent() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-background px-4">
      <div className="max-w-md text-center">
        <h1 className="text-xl font-semibold text-foreground">页面未找到</h1>
        <p className="mt-2 text-sm text-muted-foreground">你访问的页面不存在，或已被移动。</p>
        <div className="mt-6">
          <Link
            to="/"
            className="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
          >
            返回首页
          </Link>
        </div>
      </div>
    </div>
  );
}

function ErrorComponent() {
  // 渲染期异常在此统一上报（含组件栈之外的兜底路径）
  const reported = useRef(false);
  useEffect(() => {
    if (reported.current) return;
    reported.current = true;
    reportError(new Error("路由渲染失败"), { source: "route-error-component" });
  }, []);

  return (
    <div className="flex min-h-screen items-center justify-center bg-background px-4">
      <div className="max-w-md text-center">
        <h1 className="text-xl font-semibold tracking-tight text-foreground">页面加载失败</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          页面暂时无法加载。你可以尝试重试，或返回首页。
        </p>
        <div className="mt-6 flex flex-wrap justify-center gap-2">
          <button
            onClick={() => window.location.reload()}
            className="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
          >
            重试
          </button>
          <a
            href="/"
            className="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent"
          >
            返回首页
          </a>
        </div>
      </div>
    </div>
  );
}

export const Route = createRootRouteWithContext<{ queryClient: QueryClient }>()({
  component: RootRoute,
  errorComponent: ErrorComponent,
  notFoundComponent: NotFoundComponent,
  beforeLoad: async ({ location }) => {
    if (location.pathname === "/auth/callback") return;
    if (typeof window === "undefined") return;

    const token = localStorage.getItem(TOKEN_KEY);
    const isAdminRoute = location.pathname.startsWith("/admin");
    const isLoginRoute = location.pathname === "/login";

    // 无 Token 访问 /admin/* → 重定向到 /login（携带 redirect 参数）
    // hash 路由模式：从 hash 提取当前路径
    if (!token && isAdminRoute) {
      const currentPath = window.location.hash.slice(1) || "/";
      throw redirect({
        to: "/login",
        search: { redirect: currentPath },
      });
    }

    // 有 Token 访问 /login → 根据角色跳转（防死循环：目标路由非 /login）
    if (token && isLoginRoute) {
      let user: { is_admin?: boolean; is_finance?: boolean } | null = null;
      try {
        const rawUser = localStorage.getItem("xiaoxia.user");
        user = rawUser ? JSON.parse(rawUser) : null;
      } catch {
        user = null;
      }

      if (user?.is_admin) {
        throw redirect({ to: "/admin" });
      } else if (user?.is_finance) {
        throw redirect({ to: "/admin/finance" });
      } else {
        throw redirect({ to: "/" });
      }
    }
  },
});

/** 路由变化时记录页面访问（hash 路由，用路径而非完整 URL） */
function PageViewTracker() {
  const path = useRouterState({ select: (state) => state.location.pathname });

  useEffect(() => {
    trackPageView(path);
  }, [path]);

  return null;
}

function RootRoute() {
  const { queryClient } = Route.useRouteContext();

  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <CartProvider>
          <PageViewTracker />
          <Outlet />
          <Toaster richColors position="top-center" />
        </CartProvider>
      </AuthProvider>
    </QueryClientProvider>
  );
}