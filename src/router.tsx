import { QueryClient } from "@tanstack/react-query";
import { createHashHistory, createRouter } from "@tanstack/react-router";
import { ApiError } from "@/lib/api";
import { routeTree } from "./routeTree.gen";

/**
 * 全局查询默认值：
 * - staleTime 30s：避免页面来回切换时的重复请求
 * - 仅网络/HTTP 错误重试 1 次；业务错误（如"库存不足"）与未登录不重试
 */
function shouldRetry(failureCount: number, error: unknown): boolean {
  if (error instanceof ApiError) {
    if (error.kind === "business" || error.kind === "unauthorized") return false;
  }
  return failureCount < 1;
}

export const getRouter = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 30_000,
        retry: shouldRetry,
        refetchOnWindowFocus: false,
      },
      mutations: { retry: false },
    },
  });
  const hashHistory = createHashHistory();

  const router = createRouter({
    routeTree,
    history: hashHistory,
    context: { queryClient },
    scrollRestoration: true,
    defaultPreloadStaleTime: 0,
  });

  return router;
};
