import { Suspense } from "react";
import { createFileRoute } from "@tanstack/react-router";
import { SiteLayout } from "@/components/site/layout";
import { Skeleton } from "@/components/ui/skeleton";
import { ContributeErrorBoundary } from "@/features/contribute/components/contribute-error-boundary";
import { lazy } from "react";
import { LazyBoundary } from "@/components/system/lazy-boundary";
const ContributePage = lazy(() =>
  import("@/features/contribute/components/contribute-page").then((m) => ({ default: m.ContributePage })),
);
import {
  contributeStreamQuery,
  DEFAULT_PARTICIPATION_TYPE,
  type ContributeView,
} from "@/features/contribute";

/**
 * 开放参与路由：只负责
 * 1. 解析 search 参数（视图、参与类型）
 * 2. loader 预取参与流数据
 * 3. Suspense / ErrorBoundary 包裹
 * 4. 渲染 ContributePage
 *
 * 参与类型与视图都按字符串透传，新增参与类型无需改动本文件。
 * 其余表单、校验、提交、错误分级逻辑均在 features/contribute 内。
 */
export const Route = createFileRoute("/contribute")({
  component: ContributeRoute,
  validateSearch: (
    search: Record<string, unknown>,
  ): { view: ContributeView; type: string } => ({
    view: search.view === "plans" ? "plans" : "participants",
    type: typeof search.type === "string" && search.type ? search.type : DEFAULT_PARTICIPATION_TYPE,
  }),
  loader: ({ context }) => context.queryClient.ensureQueryData(contributeStreamQuery),
});

function ContributeRoute() {
  const { view, type } = Route.useSearch();
  const navigate = Route.useNavigate();

  return (
    <ContributeErrorBoundary>
      <Suspense fallback={<ContributeFallback />}>
        <ContributePage
          view={view}
          typeId={type}
          onViewChange={(next) =>
            void navigate({
              search: (prev: { view: ContributeView; type: string }) => ({ ...prev, view: next }),
              replace: true,
            })
          }
        />
      </Suspense>
    </ContributeErrorBoundary>
  );
}

function ContributeFallback() {
  return (
    <SiteLayout>
      <div className="mx-auto max-w-6xl px-4 py-20 sm:px-6">
        <Skeleton className="h-8 w-40" />
        <Skeleton className="mt-6 h-24 w-full max-w-2xl" />
        <div className="mt-12 grid gap-4 sm:grid-cols-3">
          <Skeleton className="h-28 rounded" />
          <Skeleton className="h-28 rounded" />
          <Skeleton className="h-28 rounded" />
        </div>
      </div>
    </SiteLayout>
  );
}