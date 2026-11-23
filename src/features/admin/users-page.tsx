import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { StatusBadge, type StatusBadgeType } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
  getAdminUsers,
  toggleUserBan,
  type AdminUserItem,
} from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

/** 禁止封禁的超级管理员 ID（与后端 AdminUserController::SUPER_ADMIN_IDS 契约一致）。 */
const SUPER_ADMIN_IDS = [1, 2];

/** 用户状态 -> 标签语义。 */
function userStatusType(isActive: boolean): StatusBadgeType {
  return isActive ? "success" : "danger";
}

/** 用户管理页：列表 + 封禁/解封（二次确认）。 */
export function UsersPage() {
  const [page, setPage] = useState(1);
  const [confirming, setConfirming] = useState<AdminUserItem | null>(null);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["admin-users", page],
    queryFn: () => getAdminUsers({ page }),
  });

  const banMutation = useMutation({
    mutationFn: (id: number) => toggleUserBan(id),
    onSuccess: (_result, _id) => {
      toast.success("操作成功", {
        description: confirming?.is_active
          ? `用户 ${confirming.name} 已封禁`
          : `用户 ${confirming?.name} 已解封`,
      });
      setConfirming(null);
      queryClient.invalidateQueries({ queryKey: ["admin-users"] });
    },
    onError: (err) => {
      toast.error("操作失败", { description: toErrorMessage(err) });
    },
  });

  const columns: Array<DataTableColumn<AdminUserItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    { key: "name", header: "用户名", cell: (row) => row.name },
    { key: "email", header: "邮箱", cell: (row) => row.email ?? "-" },
    {
      key: "is_admin",
      header: "角色",
      cell: (row) =>
        row.is_admin ? (
          <StatusBadge status="管理员" type="warning" />
        ) : (
          <StatusBadge status="普通用户" type="default" />
        ),
    },
    {
      key: "is_active",
      header: "状态",
      cell: (row) =>
        row.is_active ? (
          <StatusBadge status="正常" type={userStatusType(true)} />
        ) : (
          <StatusBadge status="已封禁" type={userStatusType(false)} />
        ),
    },
    { key: "created_at", header: "注册时间", cell: (row) => row.created_at ?? "-" },
    {
      key: "actions",
      header: "操作",
      cell: (row) =>
        SUPER_ADMIN_IDS.includes(row.id) ? (
          <span className="text-xs text-slate-500">超级管理员不可操作</span>
        ) : (
          <Button
            variant={row.is_active ? "destructive" : "outline"}
            size="sm"
            disabled={banMutation.isPending}
            onClick={() => setConfirming(row)}
          >
            {row.is_active ? "封禁" : "解封"}
          </Button>
        ),
    },
  ];

  const lastPage = data?.last_page ?? 1;

  return (
    <AdminPageLayout title="用户管理" description="查看用户列表与封禁/解封操作">
      {isLoading ? (
        <div className="space-y-2" aria-label="加载中">
          {Array.from({ length: 8 }).map((_, i) => (
            <div
              key={i}
              className="h-11 animate-pulse rounded-md border border-zinc-200 bg-zinc-100"
              style={{ animationDelay: `${i * 60}ms` }}
            />
          ))}
        </div>
      ) : (
        <DataTable
          columns={columns}
          data={data?.list ?? []}
          isLoading={false}
          emptyText="暂无用户"
          pagination={
            lastPage > 1 ? (
              <div className="flex items-center justify-between text-sm text-gray-500">
                <span>
                  第 {data?.current_page ?? page} / {lastPage} 页，共 {data?.total ?? 0} 条
                </span>
                <div className="flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={page <= 1}
                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                  >
                    上一页
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={page >= lastPage}
                    onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                  >
                    下一页
                  </Button>
                </div>
              </div>
            ) : null
          }
        />
      )}

      <AlertDialog
        open={confirming !== null}
        onOpenChange={(open) => {
          if (!open && !banMutation.isPending) setConfirming(null);
        }}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {confirming?.is_active ? "确认封禁用户" : "确认解封用户"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {confirming?.is_active
                ? `确认封禁用户 ${confirming?.name}（${confirming?.email}）？封禁后该用户将无法登录和调用 API。`
                : `确认解封用户 ${confirming?.name}（${confirming?.email}）？解封后该用户可正常使用系统。`}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={banMutation.isPending}>取消</AlertDialogCancel>
            <button
              type="button"
              className="inline-flex h-9 items-center justify-center whitespace-nowrap rounded-md bg-zinc-900 px-3 text-sm font-medium text-zinc-50 transition-colors hover:bg-zinc-800 disabled:pointer-events-none disabled:opacity-50"
              disabled={banMutation.isPending}
              onClick={() => {
                if (confirming) banMutation.mutate(confirming.id);
              }}
            >
              {banMutation.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  处理中…
                </>
              ) : (
                "确认"
              )}
            </button>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </AdminPageLayout>
  );
}
