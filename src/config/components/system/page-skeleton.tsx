import { Skeleton } from "@/components/ui/skeleton";

/** 路由级懒加载的占位骨架：保持首屏结构稳定，避免加载时布局跳动 */
export function PageSkeleton() {
  return (
    <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6" aria-busy="true" aria-live="polite">
      <Skeleton className="h-8 w-44" />
      <Skeleton className="mt-6 h-4 w-72" />
      <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Skeleton className="h-40 rounded-xl" />
        <Skeleton className="h-40 rounded-xl" />
        <Skeleton className="h-40 rounded-xl" />
      </div>
    </div>
  );
}
