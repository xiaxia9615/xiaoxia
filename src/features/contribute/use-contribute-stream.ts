import { useSuspenseQuery } from "@tanstack/react-query";
import { listContributeItems, type ContributeItem } from "@/lib/api";

export interface ContributeStreamData {
  rows: ContributeItem[];
  total: number;
}

export const CONTRIBUTE_STREAM_KEY = ["contribute-stream"];

/** 路由 loader 预取数据用（失败时数据层已降级为空列表，不会阻塞渲染）。 */
export const contributeStreamQuery = {
  queryKey: CONTRIBUTE_STREAM_KEY,
  queryFn: listContributeItems,
};

/**
 * 参与流数据：由路由 loader 预取，组件内以 Suspense 读取。
 * 列表属展示性数据，接口失败由数据层降级为空列表（不再整页报错）。
 */
export function useContributeStream() {
  const query = useSuspenseQuery<ContributeStreamData>(contributeStreamQuery);

  return {
    items: query.data.rows,
    total: query.data.total,
    isLoading: query.isFetching,
    refetch: query.refetch,
  };
}
