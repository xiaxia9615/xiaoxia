import { Link } from "@tanstack/react-router";
import { ArrowLeft, MapPin, Package, Receipt, Truck } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { formatPrice } from "@/data/products";
import { AppImage } from "@/components/system/app-image";
import { useOrderDetail } from "../use-order-detail";

/** 订单详情页：业务逻辑在 useOrderDetail，组件只负责渲染。 */
export function OrderDetailPage({ orderId }: { orderId: string }) {
  const {
    order,
    isLoading,
    cancelMut,
    deleteMut,
    payMut,
    aftersaleMut,
    asTarget,
    setAsTarget,
    asReason,
    setAsReason,
  } = useOrderDetail(orderId);

  const isPending = order?.order_status === 0;
  const isVirtual = order?.nature === "virtual";

  return (
    <SiteLayout>
      <main className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6">
        <Button asChild variant="ghost" size="sm" className="-ml-2 mb-6 text-muted-foreground">
          <Link to="/orders"><ArrowLeft className="size-4" /> 返回订单列表</Link>
        </Button>

        {isLoading ? (
          <Skeleton className="h-64 rounded-xl" />
        ) : !order ? (
          <div className="rounded-xl border border-dashed bg-card/60 py-20 text-center text-muted-foreground">
            订单不存在
          </div>
        ) : (
          <div className="space-y-5">
            <header className="flex items-center justify-between rounded-xl border bg-card p-6">
              <div>
                <p className="text-xs text-muted-foreground">订单号 {order.order_sn}</p>
                <h1 className="mt-1 font-display text-2xl font-semibold text-foreground">{order.status_name || "订单"}</h1>
              </div>
              <span className={`rounded px-3 py-1 text-xs font-semibold ${isVirtual && order.pay_status === 1 ? "bg-emerald-500/15 text-emerald-600" : isPending ? "bg-clay text-clay-foreground" : "bg-surface text-muted-foreground"}`}>
                {isVirtual && order.pay_status === 1 ? "已自动交付" : order.pay_status === 1 ? "已支付" : order.status_name}
              </span>
            </header>

            {!isVirtual && order.receiver_name && (
              <section className="rounded-xl border bg-card p-6">
                <p className="mb-3 flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">
                  <MapPin className="size-4" /> 收货信息
                </p>
                <p className="text-sm font-medium text-foreground">{order.receiver_name} {order.receiver_mobile}</p>
                <p className="mt-1 text-sm text-muted-foreground">{order.receiver_address}</p>
              </section>
            )}

            {!isVirtual && (
              <section className="rounded-xl border bg-card p-6">
                <p className="mb-3 flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">
                  <Truck className="size-4" /> 物流信息
                </p>
                {order.tracking_no ? (
                  <p className="text-sm text-foreground">快递单号：{order.tracking_no}</p>
                ) : (
                  <p className="text-sm text-muted-foreground">
                    {order.order_status === 2 ? "待发货" : "暂无物流信息"}
                  </p>
                )}
              </section>
            )}

            <section className="rounded-xl border bg-card p-6">
              <p className="mb-4 flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">
                <Package className="size-4" /> 商品清单
              </p>
              <ul className="space-y-4">
                {order.items.map((it) => (
                  <li key={it.id} className="flex items-center gap-4">
                    {it.image ? (
                      <AppImage src={it.image} alt={it.name} className="size-16 rounded-xl object-cover" />
                    ) : (
                      <span className="flex size-16 items-center justify-center rounded-xl bg-surface text-muted-foreground">{it.name.slice(0, 1)}</span>
                    )}
                    <div className="min-w-0 flex-1">
                      <Link to={`/products/${it.goods_id}`} className="block truncate text-sm font-medium text-foreground hover:underline">
                        {it.name}
                      </Link>
                      <p className="mt-0.5 text-xs text-muted-foreground">× {it.quantity}</p>
                    </div>
                    <div className="text-right">
                      <span className="text-sm font-medium text-foreground">{formatPrice(it.price)}</span>
                      {order.pay_status === 1 && (
                        <button
                          className="mt-1 block text-xs font-medium text-clay underline-offset-2 hover:underline"
                          onClick={() => setAsTarget({ orderDetailId: it.id, goodsName: it.name })}
                        >
                          申请售后
                        </button>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
              <div className="mt-5 space-y-1 border-t pt-4 text-sm">
                <p className="flex justify-between text-muted-foreground"><span>商品小计</span><span>{formatPrice(order.goods_amount)}</span></p>
                <p className="flex justify-between text-muted-foreground"><span>运费</span><span>{order.shipping_fee > 0 ? formatPrice(order.shipping_fee) : "免运费"}</span></p>
                <p className="flex justify-between pt-2 text-base font-semibold text-foreground"><span>实付</span><span>{formatPrice(order.pay_amount)}</span></p>
              </div>
              {order.buyer_remark && <p className="mt-3 text-xs text-muted-foreground">备注：{order.buyer_remark}</p>}
            </section>

            <div className="flex flex-wrap gap-3">
              {isPending && (
                <Button className="flex-1 rounded" disabled={payMut.isPending} onClick={() => payMut.mutate()}>
                  <Receipt className="size-4" /> 去支付
                </Button>
              )}
              {isPending && (
                <Button variant="outline" className="flex-1 rounded" disabled={cancelMut.isPending} onClick={() => cancelMut.mutate()}>
                  取消订单
                </Button>
              )}
              <Button variant="ghost" className="flex-1 rounded text-muted-foreground" disabled={deleteMut.isPending} onClick={() => deleteMut.mutate()}>
                删除订单
              </Button>
            </div>
          </div>
        )}

        <Dialog open={asTarget !== null} onOpenChange={(o) => !o && setAsTarget(null)}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>申请售后</DialogTitle>
            </DialogHeader>
            {asTarget && <p className="text-sm text-muted-foreground">商品：{asTarget.goodsName}</p>}
            <div className="space-y-2">
              <Label htmlFor="as-reason">申请原因</Label>
              <Input id="as-reason" value={asReason} onChange={(e) => setAsReason(e.target.value)} placeholder="请填写申请原因" />
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setAsTarget(null)}>取消</Button>
              <Button disabled={aftersaleMut.isPending} onClick={() => aftersaleMut.mutate()}>
                {aftersaleMut.isPending ? "提交中…" : "提交申请"}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

      </main>
    </SiteLayout>
  );
}