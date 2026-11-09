import { Suspense, type ReactNode } from "react";
import { PageSkeleton } from "./page-skeleton";

/**
 * 路由级代码分割的统一边界。
 *
 * 路由文件里对页面组件用 React.lazy 动态导入，再用本组件包裹，
 * 使每个页面独立成块：首屏只加载当前页所需的业务代码。
 *
 * 注意：lazy 组件须在模块作用域创建，避免重渲染时重复请求。
 */
export function LazyBoundary({ children }: { children: ReactNode }) {
  return <Suspense fallback={<PageSkeleton />}>{children}</Suspense>;
}

export { PageSkeleton };
