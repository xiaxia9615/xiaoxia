import { createFileRoute } from "@tanstack/react-router";
import { useRequireLogin } from "@/features/account/use-require-login";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const OrdersPage = lazy(() =>
  import("@/features/order/components/orders-page").then((m) => ({ default: m.OrdersPage })),
);
import { SiteLayout } from "@/components/site/layout";
import { Skeleton } from "@/components/ui/skeleton";

/** 我的订单：路由只做登录守卫与渲染。 */
export const Route = createFileRoute("/orders")({
  component: OrdersRoute,
});

function OrdersRoute() {
  const ready = useRequireLogin("/orders");

  if (!ready) {
    return (
      <SiteLayout>
        <div className="mx-auto max-w-3xl px-4 py-16 sm:px-6">
          <Skeleton className="h-8 w-40" />
          <Skeleton className="mt-6 h-40 w-full rounded-xl" />
        </div>
      </SiteLayout>
    );
  }

  return (
    <LazyBoundary>
      <OrdersPage />
    </LazyBoundary>
  );
}
