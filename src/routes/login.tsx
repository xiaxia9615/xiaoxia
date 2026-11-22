import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
import { requireGuest } from "@/lib/auth/guard";
const LoginPage = lazy(() =>
  import("@/features/account/components/login-page").then((m) => ({ default: m.LoginPage })),
);

/** 登录 / 注册：路由只解析 redirect 参数并渲染；已登录用户按身份跳转。 */
export const Route = createFileRoute("/login")({
  beforeLoad: requireGuest,
  component: LoginRoute,
  validateSearch: (search: Record<string, unknown>): { redirect?: string } => ({
    redirect: typeof search.redirect === "string" ? search.redirect : undefined,
  }),
});

function LoginRoute() {
  const { redirect } = Route.useSearch();
  return (
    <LazyBoundary>
      <LoginPage redirect={redirect} />
    </LazyBoundary>
  );
}
