import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";

/** 首页：新中式·数字阵法。路由只负责懒加载与边界包裹。 */
const HomePage = lazy(() =>
  import("@/features/home/components/home-page").then((m) => ({ default: m.HomePage })),
);

export const Route = createFileRoute("/")({
  component: HomeRoute,
});

function HomeRoute() {
  return (
    <LazyBoundary>
      <HomePage />
    </LazyBoundary>
  );
}
