import { requireAuth } from "@/lib/auth/guard";
import { createFileRoute } from "@tanstack/react-router";
import { useAuth } from "@/features/account/auth-provider";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const PaySelfCheckPage = lazy(() =>
  import("@/features/payment/self-check/components/pay-self-check-page").then((m) => ({ default: m.PaySelfCheckPage })),
);

/** 支付自检终端：路由只解析登录态并渲染，自检状态机在 features/payment/self-check。 */
export const Route = createFileRoute("/pay-self-check")({
  beforeLoad: requireAuth,
  component: PaySelfCheckRoute,
});

function PaySelfCheckRoute() {
  const { user, loading } = useAuth();
  return (
    <LazyBoundary>
      <PaySelfCheckPage
        userLabel={user?.nickname ?? user?.name ?? null}
        authLoading={loading}
        needLogin={!loading && !user}
      />
    </LazyBoundary>
  );
}
