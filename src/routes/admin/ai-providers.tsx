import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import {
  getAdminAiProviders,
  type AdminAiProviderItem,
} from "@/lib/api/admin";
import { EditProviderDialog } from "@/features/admin/edit-provider-dialog";

function AiProvidersPage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<AdminAiProviderItem | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ["admin-ai-providers", page],
    queryFn: () => getAdminAiProviders({ page }),
  });

  const columns: Array<DataTableColumn<AdminAiProviderItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    {
      key: "name",
      header: "供应商",
      cell: (row) => row.display_name ?? row.name,
    },
    { key: "driver", header: "驱动", cell: (row) => row.driver },
    { key: "model_name", header: "默认模型", cell: (row) => row.model_name },
    {
      key: "base_url",
      header: "Endpoint",
      cell: (row) => (
        <span className="font-mono text-xs text-gray-500">{row.base_url ?? "-"}</span>
      ),
    },
    {
      key: "masked_key",
      header: "API Key",
      cell: (row) => (
        <code className="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-600">
          {row.masked_key}
        </code>
      ),
    },
    { key: "models_count", header: "模型数", cell: (row) => row.models_count },
    {
      key: "enabled",
      header: "状态",
      cell: (row) =>
        row.enabled ? (
          <StatusBadge status="启用" type="success" />
        ) : (
          <StatusBadge status="停用" type="danger" />
        ),
    },
    {
      key: "actions",
      header: "操作",
      cell: (row) => (
        <Button variant="outline" size="sm" onClick={() => setEditing(row)}>
          编辑
        </Button>
      ),
    },
  ];

  return (
    <AdminPageLayout title="AI 供应商" description="供应商接入配置与密钥管理">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无供应商"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />
      <EditProviderDialog
        provider={editing}
        open={editing !== null}
        onOpenChange={(open) => {
          if (!open) setEditing(null);
        }}
      />
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/ai-providers")({
  component: AiProvidersPage,
});
