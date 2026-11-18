import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import { getAdminApiKeys, type AdminApiKeyItem } from "@/lib/api/admin";
import { RevokeApiKeyDialog } from "@/features/admin/revoke-api-key-dialog";

function ApiKeysPage() {
  const [page, setPage] = useState(1);
  const [revoking, setRevoking] = useState<AdminApiKeyItem | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ["admin-api-keys", page],
    queryFn: () => getAdminApiKeys({ page }),
  });

  const columns: Array<DataTableColumn<AdminApiKeyItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    {
      key: "user_email",
      header: "所属用户",
      cell: (row) => row.user_email ?? `user#${row.user_id}`,
    },
    { key: "name", header: "名称", cell: (row) => row.name },
    {
      key: "masked_key",
      header: "Key",
      cell: (row) => (
        <code className="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-600">
          {row.masked_key}
        </code>
      ),
    },
    {
      key: "status",
      header: "状态",
      cell: (row) =>
        row.status === 1 ? (
          <StatusBadge status="active" type="success" />
        ) : (
          <StatusBadge status="revoked" type="danger" />
        ),
    },
    {
      key: "last_used_at",
      header: "最近使用",
      cell: (row) => row.last_used_at ?? "未使用",
    },
    {
      key: "expires_at",
      header: "过期时间",
      cell: (row) => row.expires_at ?? "永不过期",
    },
    {
      key: "actions",
      header: "操作",
      cell: (row) =>
        row.status === 1 ? (
          <Button
            variant="outline"
            size="sm"
            className="text-red-600 hover:text-red-700"
            onClick={() => setRevoking(row)}
          >
            吊销
          </Button>
        ) : (
          <span className="text-xs text-gray-400">已吊销</span>
        ),
    },
  ];

  return (
    <AdminPageLayout title="API Keys" description="平台 API 密钥管理与吊销">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无 API Key"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />
      <RevokeApiKeyDialog
        item={revoking}
        open={revoking !== null}
        onOpenChange={(open) => {
          if (!open) setRevoking(null);
        }}
      />
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/api-keys")({
  component: ApiKeysPage,
});
