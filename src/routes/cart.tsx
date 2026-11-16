import { requireAuth } from "@/lib/auth/guard";
import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const CartPage = lazy(() =>
  import("@/features/cart/components/cart-page").then((m) => ({ default: m.CartPage })),
);
import { useAuth } from "@/features/account/auth-provider";

/** 购物车：路由只解析登录态并渲染（未登录也可浏览本地购物车）。 */
export const Route = createFileRoute("/cart")({
  beforeLoad: requireAuth,
  component: CartRoute,
});

function CartRoute() {
  const { user, loading } = useAuth();
  if (loading) return null;
  return (
    <LazyBoundary>
      <CartPage loggedIn={Boolean(user)} />
    </LazyBoundary>
  );
}
