import { requireAuth } from "@/lib/auth/guard";
import { createFileRoute } from "@tanstack/react-router";
import { useRequireLogin } from "@/features/account/use-require-login";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const OrderDetailPage = lazy(() =>
  import("@/features/order/components/order-detail-page").then((m) => ({ default: m.OrderDetailPage })),
);

/** 订单详情：路由只解析参数、做登录守卫并渲染。 */
export const Route = createFileRoute("/orders_/$orderId")({
  beforeLoad: requireAuth,
  component: OrderDetailRoute,
});

function OrderDetailRoute() {
  const { orderId } = Route.useParams();
  const ready = useRequireLogin(`/orders/${orderId}`);
  if (!ready) return null;
  return (
    <LazyBoundary>
      <OrderDetailPage orderId={orderId} />
    </LazyBoundary>
  );
}
