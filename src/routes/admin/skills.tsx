import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import { getAdminSkills, toggleAdminSkillStatus, type AdminSkillItem } from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

function SkillsPage() {
  const [page, setPage] = useState(1);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["admin-skills", page],
    queryFn: () => getAdminSkills({ page }),
  });

  const toggleMutation = useMutation({
    mutationFn: (row: AdminSkillItem) => toggleAdminSkillStatus(row.id),
    onSuccess: (result) => {
      toast.success(result.status === 1 ? "技能已上架" : "技能已下架", {
        description: `商品 #${result.product_id} 状态已更新。`,
      });
      queryClient.invalidateQueries({ queryKey: ["admin-skills"] });
    },
    onError: (err) => {
      toast.error("操作失败", { description: toErrorMessage(err) });
    },
  });

  const columns: Array<DataTableColumn<AdminSkillItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    { key: "product_name", header: "技能名称", cell: (row) => row.product_name ?? "-" },
    {
      key: "endpoint",
      header: "Endpoint",
      cell: (row) => (
        <span className="font-mono text-xs text-gray-500">{row.endpoint ?? "-"}</span>
      ),
    },
    { key: "method", header: "方法", cell: (row) => row.method ?? "-" },
    {
      key: "pricing_type",
      header: "计费",
      cell: (row) => (row.pricing_type === 1 ? "按次" : "按量"),
    },
    {
      key: "masked_api_key",
      header: "API Key",
      cell: (row) => (
        <code className="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-600">
          {row.masked_api_key}
        </code>
      ),
    },
    { key: "calls_count", header: "调用量", cell: (row) => row.calls_count },
    {
      key: "product_status",
      header: "状态",
      cell: (row) =>
        row.product_status === 1 ? (
          <StatusBadge status="已上架" type="success" />
        ) : (
          <StatusBadge status="已下架" type="danger" />
        ),
    },
    {
      key: "actions",
      header: "操作",
      cell: (row) => (
        <Button
          variant="outline"
          size="sm"
          disabled={toggleMutation.isPending}
          onClick={() => toggleMutation.mutate(row)}
        >
          {row.product_status === 1 ? "下架" : "上架"}
        </Button>
      ),
    },
  ];

  return (
    <AdminPageLayout title="技能管理" description="技能列表与上下架管理">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无技能"
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

export const Route = createFileRoute("/admin/skills")({
  component: SkillsPage,
});
