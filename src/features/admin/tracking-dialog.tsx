import { useQuery } from "@tanstack/react-query";
import { Loader2, Package, Truck } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { getOrderTracking, type AdminOrderItem } from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

/** 物流轨迹查询弹窗。 */
export function TrackingDialog({
  order,
  open,
  onOpenChange,
}: {
  order: AdminOrderItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const { data, isLoading, error } = useQuery({
    queryKey: ["order-tracking", order?.id],
    queryFn: () => getOrderTracking(order!.id),
    enabled: open && order !== null,
  });

  return (
    <Dialog open={open} onOpenChange={(o) => onOpenChange(o)}>
      <DialogContent className="border-white/10 bg-zinc-950/90 backdrop-blur-xl sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2 text-slate-100">
            <Truck className="size-5 text-emerald-400" />
            物流轨迹
          </DialogTitle>
          <DialogDescription className="text-slate-500">
            订单 {order?.order_no} 的物流信息
          </DialogDescription>
        </DialogHeader>

        {isLoading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="h-6 w-6 animate-spin text-emerald-400" />
            <span className="ml-2 text-sm text-slate-400">查询中…</span>
          </div>
        ) : error ? (
          <div className="py-8 text-center">
            <p className="text-sm text-red-400">查询失败</p>
            <p className="mt-1 text-xs text-slate-500">{toErrorMessage(error)}</p>
          </div>
        ) : data ? (
          <div className="space-y-4">
            {/* 物流信息摘要 */}
            <div className="rounded-lg border border-white/10 bg-black/40 p-4">
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <span className="text-slate-500">快递公司</span>
                  <p className="mt-0.5 font-medium text-slate-200">
                    {data.express_company_name || data.express_company}
                  </p>
                </div>
                <div>
                  <span className="text-slate-500">物流单号</span>
                  <p className="mt-0.5 font-medium text-slate-200">{data.tracking_no}</p>
                </div>
                <div>
                  <span className="text-slate-500">发货时间</span>
                  <p className="mt-0.5 font-medium text-slate-200">
                    {data.shipped_at ?? "-"}
                  </p>
                </div>
              </div>
            </div>

            {/* 轨迹时间线 */}
            {data.tracks.length > 0 ? (
              <div className="max-h-80 space-y-0 overflow-y-auto">
                {data.tracks.map((track, idx) => (
                  <div key={idx} className="flex gap-3">
                    {/* 时间线轴 */}
                    <div className="flex flex-col items-center">
                      <div
                        className={`mt-1 size-2.5 shrink-0 rounded-full ${
                          idx === 0 ? "bg-emerald-400" : "bg-slate-600"
                        }`}
                      />
                      {idx < data.tracks.length - 1 && (
                        <div className="w-px flex-1 bg-slate-700" />
                      )}
                    </div>
                    {/* 内容 */}
                    <div className="pb-4">
                      <p className="text-xs text-slate-500">{track.time}</p>
                      <p className={`mt-0.5 text-sm ${idx === 0 ? "text-slate-100" : "text-slate-400"}`}>
                        {track.context}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="flex flex-col items-center py-8 text-center">
                <Package className="size-8 text-slate-600" />
                <p className="mt-2 text-sm text-slate-500">暂无物流轨迹信息</p>
                <p className="mt-1 text-xs text-slate-600">
                  可能是快递公司尚未揽收或接口暂时不可用
                </p>
              </div>
            )}
          </div>
        ) : null}

        <div className="flex justify-end">
          <Button
            type="button"
            variant="outline"
            className="border-white/10 bg-transparent text-slate-300 hover:bg-white/5"
            onClick={() => onOpenChange(false)}
          >
            关闭
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
