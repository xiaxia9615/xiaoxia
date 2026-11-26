import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { Truck } from "lucide-react";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import {
  getAdminOrders,
  type AdminOrderItem,
} from "@/lib/api/admin";
import { formatPrice } from "@/data/products";
import {
  ShipOrderDialog,
  orderStatusType,
  orderStatusLabel,
} from "@/features/admin/ship-order-dialog";
import { TrackingDialog } from "@/features/admin/tracking-dialog";

function OrdersPage() {
  const [page, setPage] = useState(1);
  const [shippingOrder, setShippingOrder] = useState<AdminOrderItem | null>(null);
  const [trackingOrder, setTrackingOrder] = useState<AdminOrderItem | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ["admin-orders", page],
    queryFn: () => getAdminOrders({ page }),
  });

  const columns: Array<DataTableColumn<AdminOrderItem>> = [
    { key: "order_no", header: "订单号", cell: (row) => row.order_no },
    { key: "product_name", header: "商品", cell: (row) => row.product_name ?? "-" },
    { key: "user_name", header: "用户", cell: (row) => row.user_name ?? "-" },
    { key: "amount", header: "金额", cell: (row) => formatPrice(row.amount ?? 0) },
    {
      key: "nature",
      header: "性质",
      cell: (row) =>
        row.nature === "virtual" ? (
          <StatusBadge status="虚拟" type="warning" />
        ) : (
          <StatusBadge status="实物" type="default" />
        ),
    },
    {
      key: "status",
      header: "状态",
      cell: (row) => (
        <StatusBadge status={orderStatusLabel(row.status ?? "")} type={orderStatusType(row.status ?? "")} />
      ),
    },
    { key: "created_at", header: "创建时间", cell: (row) => row.created_at ?? "-" },
    {
      key: "actions",
      header: "操作",
      cell: (row) => {
        if (row.nature === "virtual") {
          return <span className="text-xs text-slate-500">已自动交付</span>;
        }
        if (row.status === "pending_shipment") {
          return (
            <Button variant="outline" size="sm" onClick={() => setShippingOrder(row)}>
              <Truck className="mr-1 h-3 w-3" />
              发货
            </Button>
          );
        }
        if (row.tracking_no) {
          return (
            <Button variant="outline" size="sm" onClick={() => setTrackingOrder(row)}>
              <Truck className="mr-1 h-3 w-3" />
              查看物流
            </Button>
          );
        }
        return <span className="text-xs text-slate-600">-</span>;
      },
    },
  ];

  return (
    <AdminPageLayout title="订单管理" description="全部订单的查询与跟踪">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无订单"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />

      <ShipOrderDialog
        order={shippingOrder}
        open={shippingOrder !== null}
        onOpenChange={(open) => {
          if (!open) setShippingOrder(null);
        }}
      />

      <TrackingDialog
        order={trackingOrder}
        open={trackingOrder !== null}
        onOpenChange={(open) => {
          if (!open) setTrackingOrder(null);
        }}
      />
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/orders")({
  component: OrdersPage,
});
