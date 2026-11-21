import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { getAdminUsers, type AdminUserItem } from "@/lib/api/admin";

function UsersPage() {
  const [page, setPage] = useState(1);
  const { data, isLoading } = useQuery({
    queryKey: ["admin-users", page],
    queryFn: () => getAdminUsers({ page }),
  });

  const columns: Array<DataTableColumn<AdminUserItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    { key: "name", header: "昵称", cell: (row) => row.name },
    { key: "email", header: "邮箱", cell: (row) => row.email },
    {
      key: "is_admin",
      header: "角色",
      cell: (row) =>
        row.is_admin ? (
          <StatusBadge status="管理员" type="warning" />
        ) : (
          <StatusBadge status="用户" type="default" />
        ),
    },
    {
      key: "is_active",
      header: "状态",
      cell: (row) =>
        row.is_active ? (
          <StatusBadge status="正常" type="success" />
        ) : (
          <StatusBadge status="禁用" type="danger" />
        ),
    },
    { key: "created_at", header: "注册时间", cell: (row) => row.created_at ?? "-" },
  ];

  return (
    <AdminPageLayout title="用户管理" description="平台用户与角色管理">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无用户"
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

export const Route = createFileRoute("/admin/users")({
  component: UsersPage,
});
