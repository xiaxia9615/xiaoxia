import { requireAuth } from "@/lib/auth/guard";
import { createFileRoute } from "@tanstack/react-router";
import { useRequireLogin } from "@/features/account/use-require-login";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const AftersalesPage = lazy(() =>
  import("@/features/order/components/aftersales-page").then((m) => ({ default: m.AftersalesPage })),
);

/** 售后申请：路由只做登录守卫与渲染。 */
export const Route = createFileRoute("/aftersales")({
  beforeLoad: requireAuth,
  component: AftersalesRoute,
});

function AftersalesRoute() {
  const ready = useRequireLogin("/aftersales");
  if (!ready) return null;
  return (
    <LazyBoundary>
      <AftersalesPage />
    </LazyBoundary>
  );
}
