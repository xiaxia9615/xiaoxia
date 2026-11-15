import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { getAdminApiKeys, revokeAdminApiKey, type AdminApiKeyItem } from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

function ApiKeysPage() {
  const [page, setPage] = useState(1);
  const [revoking, setRevoking] = useState<AdminApiKeyItem | null>(null);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["admin-api-keys", page],
    queryFn: () => getAdminApiKeys({ page }),
  });

  const revokeMutation = useMutation({
    mutationFn: (row: AdminApiKeyItem) => revokeAdminApiKey(row.id),
    onSuccess: () => {
      setRevoking(null);
      toast.success("Key 已吊销", { description: "该 Key 立即失效。" });
      queryClient.invalidateQueries({ queryKey: ["admin-api-keys"] });
    },
    onError: (err) => {
      toast.error("吊销失败", { description: toErrorMessage(err) });
    },
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
      <AlertDialog
        open={revoking !== null}
        onOpenChange={(open) => {
          if (!open) setRevoking(null);
        }}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>确认吊销此 Key？</AlertDialogTitle>
            <AlertDialogDescription>
              确认吊销 {revoking?.name}（{revoking?.masked_key}）？吊销后立即失效，不可恢复。
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={revokeMutation.isPending}>取消</AlertDialogCancel>
            <button
              type="button"
              className="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md bg-red-600 px-3 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:pointer-events-none disabled:opacity-50"
              disabled={revokeMutation.isPending}
              onClick={() => {
                if (revoking) revokeMutation.mutate(revoking);
              }}
            >
              {revokeMutation.isPending ? "吊销中…" : "确认吊销"}
            </button>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/api-keys")({
  component: ApiKeysPage,
});
