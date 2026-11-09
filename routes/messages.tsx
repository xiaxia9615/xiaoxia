import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const MessagesPage = lazy(() =>
  import("@/features/account/components/messages-page").then((m) => ({ default: m.MessagesPage })),
);
import { useRequireLogin } from "@/features/account/use-require-login";

/** 通知中心：路由只做登录守卫与渲染。 */
export const Route = createFileRoute("/messages")({
  component: MessagesRoute,
});

function MessagesRoute() {
  const ready = useRequireLogin("/messages");
  if (!ready) return null;
  return (
    <LazyBoundary>
      <MessagesPage />
    </LazyBoundary>
  );
}
