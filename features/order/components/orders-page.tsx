import { Link } from "@tanstack/react-router";
import { ArrowLeft, PackageOpen, Receipt } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { toErrorMessage } from "@/lib/api";
import { AppImage } from "@/components/system/app-image";
import { useOrders } from "../use-orders";

function formatAmount(value: number | string | undefined): string {
  const n = Number(value);
  if (!Number.isFinite(n)) return "¥0";
  return `¥${n.toFixed(n % 1 === 0 ? 0 : 2)}`;
}

/** 我的订单页：业务逻辑在 useOrders，组件只负责渲染。 */
export function OrdersPage() {
  const { orders, isLoading, isError, error, refetch, payingId, pay, statusText } = useOrders();

  return (
    <SiteLayout>
      <div className="mx-auto max-w-3xl px-4 pb-10 pt-12 sm:px-6">
        <Link
          to="/"
          className="mb-6 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-clay"
        >
          <ArrowLeft className="size-4" />
          返回首页
        </Link>

        <div className="flex items-center gap-2">
          <Receipt className="size-6 text-clay" />
          <h1 className="font-display text-3xl font-bold text-ink">我的订单</h1>
        </div>

        <div className="mt-8">
          {isLoading ? (
            <div className="space-y-4">
              <Skeleton className="h-36 w-full rounded-xl" />
              <Skeleton className="h-36 w-full rounded-xl" />
            </div>
          ) : isError ? (
            <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-16 text-center">
              <Receipt className="size-10 text-muted-foreground/40" />
              <p className="mt-4 font-display text-xl text-foreground">订单加载失败</p>
              <p className="mt-1 text-sm text-muted-foreground">
                {toErrorMessage(error, "网络异常，请稍后重试")}
              </p>
              <Button className="mt-6 rounded px-6" onClick={() => void refetch()}>重新加载</Button>
            </div>
          ) : orders.length > 0 ? (
            <div className="space-y-4">
              {orders.map((order) => {
                const isPending = Number(order.order_status) === 0;
                return (
                  <div
                    key={order.id}
                    className="rounded-xl border border-border/70 bg-card p-5 shadow-soft"
                  >
                    <div className="flex items-center justify-between border-b border-border/60 pb-3">
                      <span className="text-sm text-muted-foreground">
                        <Link to={`/orders/${order.id}`} className="hover:underline">
                          订单号 {order.order_sn ?? order.id}
                        </Link>
                      </span>
                      <span className="rounded bg-clay/10 px-2.5 py-1 text-xs font-medium text-clay">
                        {order.status_name || statusText(order.order_status)}
                      </span>
                    </div>
                    <div className="flex items-center gap-4 py-4">
                      {order.goods_image ? (
                        <AppImage
                          src={order.goods_image}
                          alt={order.goods_name ?? "商品"}
                          className="size-16 rounded-xl bg-surface object-cover"
                        />
                      ) : (
                        <span className="grid size-16 place-items-center rounded-xl bg-surface text-muted-foreground">
                          <PackageOpen className="size-6" />
                        </span>
                      )}
                      <div className="min-w-0 flex-1">
                        <p className="truncate font-medium text-ink">
                          {order.goods_name ?? "商品"}
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground">
                          {order.quantity != null ? `数量 ×${order.quantity}` : ""}
                        </p>
                      </div>
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-3">
                      <span className="text-sm text-muted-foreground">
                        {order.created_at ?? ""}
                      </span>
                      <span className="font-display font-bold text-clay">
                        {formatAmount(order.total_amount ?? order.total_price ?? order.price)}
                      </span>
                    </div>
                    {isPending && (
                      <div className="mt-3 flex justify-end border-t border-border/60 pt-3">
                        <Button
                          size="sm"
                          className="rounded"
                          disabled={payingId === order.id}
                          onClick={() => pay(order.id)}
                        >
                          {payingId === order.id ? "发起中…" : "去支付"}
                        </Button>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="flex flex-col items-center rounded-xl border border-dashed border-border bg-card px-6 py-20 text-center">
              <PackageOpen className="size-8 text-muted-foreground" />
              <h2 className="mt-4 font-display text-lg font-semibold text-ink">
                还没有订单
              </h2>
              <p className="mt-1 max-w-xs text-sm text-muted-foreground">
                去挑选几件喜欢的生活好物吧。
              </p>
              <Button asChild className="mt-6 rounded">
                <Link to="/products">去选购</Link>
              </Button>
            </div>
          )}
        </div>
      </div>
    </SiteLayout>
  );
}