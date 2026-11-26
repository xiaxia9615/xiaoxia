import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  shipAdminOrder,
  type AdminOrderItem,
} from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";
import { type StatusBadgeType } from "@/config/components/admin/StatusBadge";

/** 快递公司选项。 */
const EXPRESS_COMPANIES = [
  { value: "SF", label: "顺丰速运" },
  { value: "YTO", label: "圆通速递" },
  { value: "ZTO", label: "中通快递" },
  { value: "YD", label: "韵达快递" },
  { value: "STO", label: "申通快递" },
  { value: "JD", label: "京东物流" },
  { value: "EMS", label: "EMS" },
  { value: "TT", label: "天天快递" },
  { value: "DBL", label: "德邦快递" },
  { value: "HTKY", label: "百世快递" },
  { value: "JTSD", label: "极兔速递" },
];

/** 订单状态 -> 标签语义。 */
export function orderStatusType(status: string): StatusBadgeType {
  if (status === "paid" || status === "completed") return "success";
  if (status === "pending" || status === "pending_shipment") return "warning";
  if (status === "cancelled" || status === "refunded") return "danger";
  return "default";
}

/** 订单状态 -> 中文标签。 */
export function orderStatusLabel(status: string): string {
  if (status === "pending") return "待支付";
  if (status === "paid") return "已支付";
  if (status === "pending_shipment") return "待发货";
  if (status === "completed") return "已完成";
  if (status === "cancelled") return "已取消";
  if (status === "refunded") return "已退款";
  return status;
}

/** 订单发货弹窗。 */
export function ShipOrderDialog({
  order,
  open,
  onOpenChange,
}: {
  order: AdminOrderItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const queryClient = useQueryClient();
  const [trackingNo, setTrackingNo] = useState("");
  const [expressCompany, setExpressCompany] = useState("SF");

  const shipMutation = useMutation({
    mutationFn: (input: { id: number; trackingNo: string; expressCompany: string }) =>
      shipAdminOrder(input.id, input.trackingNo, input.expressCompany),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin-orders"] });
      toast.success("发货成功", { description: "物流单号已记录，订单已完成。" });
      onOpenChange(false);
      setTrackingNo("");
      setExpressCompany("SF");
    },
    onError: (err) => {
      toast.error("发货失败", { description: toErrorMessage(err) });
    },
  });

  const submitShip = () => {
    if (!order) return;
    if (!trackingNo.trim()) {
      toast.error("请填写物流单号");
      return;
    }
    shipMutation.mutate({ id: order.id, trackingNo: trackingNo.trim(), expressCompany });
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(o) => {
        if (!o && !shipMutation.isPending) onOpenChange(false);
      }}
    >
      <DialogContent className="border-white/10 bg-zinc-950/90 backdrop-blur-xl sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="text-slate-100">订单发货</DialogTitle>
          <DialogDescription className="text-slate-500">
            订单 {order?.order_no}（{order?.product_name}），选择快递公司并填写物流单号完成发货。
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4">
          <div className="space-y-2">
            <Label className="text-slate-300">快递公司</Label>
            <Select value={expressCompany} onValueChange={setExpressCompany}>
              <SelectTrigger className="border-white/10 bg-black/40 text-slate-100 focus:border-emerald-400/50 focus:ring-emerald-400/30">
                <SelectValue placeholder="选择快递公司" />
              </SelectTrigger>
              <SelectContent className="border-white/10 bg-zinc-950/95 backdrop-blur-xl">
                {EXPRESS_COMPANIES.map((c) => (
                  <SelectItem key={c.value} value={c.value}>
                    {c.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label className="text-slate-300">物流单号</Label>
            <Input
              value={trackingNo}
              onChange={(e) => setTrackingNo(e.target.value)}
              placeholder="请输入物流单号"
              className="border-white/10 bg-black/40 text-slate-100 placeholder:text-slate-600 focus-visible:border-emerald-400/50 focus-visible:ring-emerald-400/30"
            />
          </div>
        </div>
        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            className="border-white/10 bg-transparent text-slate-300 hover:bg-white/5"
            disabled={shipMutation.isPending}
            onClick={() => onOpenChange(false)}
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
  );
}
