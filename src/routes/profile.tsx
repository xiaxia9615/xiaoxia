import { requireAuth } from "@/lib/auth/guard";
import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const ProfilePage = lazy(() =>
  import("@/features/account/components/profile-page").then((m) => ({ default: m.ProfilePage })),
);
import { useRequireLogin } from "@/features/account/use-require-login";

/** 个人资料：路由只做登录守卫与渲染（守卫替代原先的渲染期跳转）。 */
export const Route = createFileRoute("/profile")({
  beforeLoad: requireAuth,
  component: ProfileRoute,
});

function ProfileRoute() {
  const ready = useRequireLogin("/profile");
  if (!ready) return null;
  return (
    <LazyBoundary>
      <ProfilePage />
    </LazyBoundary>
  );
}
