import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { getAdminApiCalls, type AdminApiCallItem } from "@/lib/api/admin";

/** api_calls.cost 单位为分（与 Blade 版本一致），展示时 /100 转元。 */
function formatCallCost(value: number): string {
  return `¥${(value / 100).toFixed(4)}`;
}

function ApiCallsPage() {
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ["admin-api-calls", page],
    queryFn: () => getAdminApiCalls({ page }),
  });

  const columns: Array<DataTableColumn<AdminApiCallItem>> = [
    { key: "created_at", header: "时间", cell: (row) => row.created_at },
    {
      key: "request_id",
      header: "请求 ID",
      cell: (row) => (
        <span className="font-mono text-xs text-gray-500">{row.request_id}</span>
      ),
    },
    {
      key: "user",
      header: "用户",
      cell: (row) =>
        row.user_name ? `${row.user_name} (#${row.user_id})` : `#${row.user_id}`,
    },
    {
      key: "product",
      header: "技能",
      cell: (row) =>
        row.product_name ? `${row.product_name} (#${row.product_id})` : `#${row.product_id}`,
    },
    {
      key: "status_code",
      header: "状态码",
      cell: (row) =>
        row.status_code === 200 ? (
          <StatusBadge status={String(row.status_code)} type="success" />
        ) : (
          <StatusBadge status={String(row.status_code)} type="danger" />
        ),
    },
    {
      key: "duration_ms",
      header: "耗时",
      cell: (row) => <span className="font-mono text-xs">{row.duration_ms}ms</span>,
    },
    {
      key: "cost",
      header: "消耗",
      cell: (row) => (
        <span className="font-mono text-xs">{formatCallCost(row.cost)}</span>
      ),
    },
  ];

  return (
    <AdminPageLayout title="调用流水" description="API 调用记录与消耗统计">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无调用记录"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/api-calls")({
  component: ApiCallsPage,
});
