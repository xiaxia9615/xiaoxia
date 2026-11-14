import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { getAdminUsers, type AdminUserItem } from "@/lib/api/generated";

function UsersPage() {
  const { data, isLoading } = useQuery({
    queryKey: ["admin-users"],
    queryFn: () => getAdminUsers(),
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
      />
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/users")({
  component: UsersPage,
});
