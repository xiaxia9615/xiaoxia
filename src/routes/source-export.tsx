import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const SourceExportPage = lazy(() =>
  import("@/features/source-export/components/source-export-page").then((m) => ({ default: m.SourceExportPage })),
);

/**
 * 源码导出（临时页面）：用于把前端源码带出平台。
 * 导出完成后删除本文件、src/features/source-export 与 router.json 中对应条目。
 */
export const Route = createFileRoute("/source-export")({
  component: () => (
    <LazyBoundary>
      <SourceExportPage />
    </LazyBoundary>
  ),
});
