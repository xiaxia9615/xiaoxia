import { requireAuth } from "@/lib/auth/guard";
import { createFileRoute } from "@tanstack/react-router";
import { useRequireLogin } from "@/features/account/use-require-login";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const CheckoutPage = lazy(() =>
  import("@/features/checkout/components/checkout-page").then((m) => ({ default: m.CheckoutPage })),
);

/** 结算：路由只解析 ids 参数、做登录守卫并渲染。 */
export const Route = createFileRoute("/checkout")({
  beforeLoad: requireAuth,
  component: CheckoutRoute,
  validateSearch: (search: Record<string, unknown>): { ids: string } => ({
    ids: typeof search.ids === "string" ? search.ids : "",
  }),
});

function CheckoutRoute() {
  const { ids } = Route.useSearch();
  const ready = useRequireLogin(`/checkout?ids=${ids}`);
  if (!ready) return null;
  return (
    <LazyBoundary>
      <CheckoutPage ids={ids} />
    </LazyBoundary>
  );
}
