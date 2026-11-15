import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { Loader2, Truck } from "lucide-react";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { StatusBadge, type StatusBadgeType } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  getAdminOrders,
  shipAdminOrder,
  type AdminOrderItem,
} from "@/lib/api/generated";
import { toErrorMessage } from "@/lib/api/request";
import { formatPrice } from "@/data/products";

/** 订单状态 -> 标签语义。 */
function orderStatusType(status: string): StatusBadgeType {
  if (status === "paid" || status === "completed") return "success";
  if (status === "pending" || status === "pending_shipment") return "warning";
  if (status === "cancelled" || status === "refunded") return "danger";
  return "default";
}

function orderStatusLabel(status: string): string {
  if (status === "pending") return "待支付";
  if (status === "paid") return "已支付";
  if (status === "pending_shipment") return "待发货";
  if (status === "completed") return "已完成";
  if (status === "cancelled") return "已取消";
  if (status === "refunded") return "已退款";
  return status;
}

function OrdersPage() {
  const queryClient = useQueryClient();
  const [shippingOrder, setShippingOrder] = useState<AdminOrderItem | null>(null);
  const [trackingNo, setTrackingNo] = useState("");

  const { data, isLoading } = useQuery({
    queryKey: ["admin-orders"],
    queryFn: () => getAdminOrders(),
  });

  const shipMutation = useMutation({
    mutationFn: (input: { id: number; trackingNo: string }) =>
      shipAdminOrder(input.id, input.trackingNo),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin-orders"] });
      toast.success("发货成功", { description: "物流单号已记录，订单已完成。" });
      setShippingOrder(null);
      setTrackingNo("");
    },
    onError: (err) => {
      toast.error("发货失败", { description: toErrorMessage(err) });
    },
  });

  const openShipDialog = (row: AdminOrderItem) => {
    setTrackingNo(row.tracking_no ?? "");
    setShippingOrder(row);
  };

  const submitShip = () => {
    if (!shippingOrder) return;
    if (!trackingNo.trim()) {
      toast.error("请填写物流单号");
      return;
    }
    shipMutation.mutate({ id: shippingOrder.id, trackingNo: trackingNo.trim() });
  };

  const columns: Array<DataTableColumn<AdminOrderItem>> = [
    { key: "order_no", header: "订单号", cell: (row) => row.order_no },
    { key: "product_name", header: "商品", cell: (row) => row.product_name ?? "-" },
    { key: "user_name", header: "用户", cell: (row) => row.user_name ?? "-" },
    { key: "amount", header: "金额", cell: (row) => formatPrice(row.amount) },
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
        <StatusBadge status={orderStatusLabel(row.status)} type={orderStatusType(row.status)} />
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
            <Button variant="outline" size="sm" onClick={() => openShipDialog(row)}>
              <Truck className="mr-1 h-3 w-3" />
              发货
            </Button>
          );
        }
        if (row.tracking_no) {
          return (
            <span className="text-xs text-slate-500" title={row.tracking_no}>
              {row.tracking_no}
            </span>
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
      />

      <Dialog
        open={shippingOrder !== null}
        onOpenChange={(open) => {
          if (!open && !shipMutation.isPending) {
            setShippingOrder(null);
          }
        }}
      >
        <DialogContent className="border-white/10 bg-zinc-950/90 backdrop-blur-xl sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="text-slate-100">订单发货</DialogTitle>
            <DialogDescription className="text-slate-500">
              订单 {shippingOrder?.order_no}（{shippingOrder?.product_name}），填写物流单号完成发货。
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2">
            <Label className="text-slate-300">物流单号</Label>
            <Input
              value={trackingNo}
              onChange={(e) => setTrackingNo(e.target.value)}
              placeholder="请输入物流单号"
              className="border-white/10 bg-black/40 text-slate-100 placeholder:text-slate-600 focus-visible:border-emerald-400/50 focus-visible:ring-emerald-400/30"
            />
          </div>
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              className="border-white/10 bg-transparent text-slate-300 hover:bg-white/5"
              disabled={shipMutation.isPending}
              onClick={() => setShippingOrder(null)}
            >
              取消
            </Button>
            <Button
              type="button"
              disabled={shipMutation.isPending}
              className="bg-emerald-600 text-white hover:bg-emerald-500"
              onClick={submitShip}
            >
              {shipMutation.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  发货中…
                </>
              ) : (
                "确认发货"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/orders")({
  component: OrdersPage,
});
