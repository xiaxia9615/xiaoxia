import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const AssistantPage = lazy(() =>
  import("@/features/assistant/components/assistant-page").then((m) => ({ default: m.AssistantPage })),
);

/** AI 导购助手：路由只做渲染，对话状态机在 features/assistant。 */
export const Route = createFileRoute("/assistant")({
  component: () => (
    <LazyBoundary>
      <AssistantPage />
    </LazyBoundary>
  ),
});
