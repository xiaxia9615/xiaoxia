import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const AddressPage = lazy(() =>
  import("@/features/account/components/address-page").then((m) => ({ default: m.AddressPage })),
);
import { useRequireLogin } from "@/features/account/use-require-login";
import { SiteLayout } from "@/components/site/layout";
import { Skeleton } from "@/components/ui/skeleton";

/** 收货地址：路由只做登录守卫与渲染，业务逻辑在 features/account。 */
export const Route = createFileRoute("/address")({
  component: AddressRoute,
});

function AddressRoute() {
  const ready = useRequireLogin("/address");

  if (!ready) {
    return (
      <SiteLayout>
        <div className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
          <Skeleton className="h-8 w-40" />
          <Skeleton className="mt-6 h-32 w-full rounded-xl" />
        </div>
      </SiteLayout>
    );
  }

  return (
    <LazyBoundary>
      <AddressPage />
    </LazyBoundary>
  );
}
