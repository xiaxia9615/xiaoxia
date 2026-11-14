import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { StatusBadge, type StatusBadgeType } from "@/config/components/admin/StatusBadge";
import { getAdminOrders, type AdminOrderItem } from "@/lib/api/generated";
import { formatPrice } from "@/data/products";

/** 订单状态 -> 标签语义。 */
function orderStatusType(status: string): StatusBadgeType {
  if (status === "paid") return "success";
  if (status === "pending") return "warning";
  if (status === "cancelled" || status === "refunded") return "danger";
  return "default";
}

function OrdersPage() {
  const { data, isLoading } = useQuery({
    queryKey: ["admin-orders"],
    queryFn: () => getAdminOrders(),
  });

  const columns: Array<DataTableColumn<AdminOrderItem>> = [
    { key: "order_no", header: "订单号", cell: (row) => row.order_no },
    { key: "product_name", header: "商品", cell: (row) => row.product_name ?? "-" },
    { key: "user_name", header: "用户", cell: (row) => row.user_name ?? "-" },
    { key: "amount", header: "金额", cell: (row) => formatPrice(row.amount) },
    {
      key: "status",
      header: "状态",
      cell: (row) => (
        <StatusBadge status={row.status} type={orderStatusType(row.status)} />
      ),
    },
    { key: "created_at", header: "创建时间", cell: (row) => row.created_at ?? "-" },
  ];

  return (
    <AdminPageLayout title="订单管理" description="全部订单的查询与跟踪">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无订单"
      />
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/orders")({
  component: OrdersPage,
});
