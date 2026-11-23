import { Link } from "@tanstack/react-router";
import { ArrowLeft, MapPin, Receipt } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { PAY_CHANNELS } from "@/features/payment";
import { cn } from "@/lib/utils";
import { formatPrice } from "@/data/products";
import { AppImage } from "@/components/system/app-image";
import { usePlaceOrder } from "../use-place-order";
import { PayQrDialog } from "@/features/payment/components/pay-qr-dialog";

/** 结算页：交易流程在 usePlaceOrder，组件只负责渲染。 */
export function CheckoutPage({ ids }: { ids: string }) {
  const { payment, setPayment, placeOrder, submitOrder, checkout, checkoutError, isLoading, addresses, qrDialog, closeQrDialog, onQrPaySuccess } =
    usePlaceOrder(ids);

  const defaultAddr = addresses.find((a) => a.is_default) ?? addresses[0];
  /** 纯虚拟订单：无需收货地址与运费（后端 buy/index 补齐 nature 后自动生效）。 */
  const isVirtualOnly =
    (checkout?.items?.length ?? 0) > 0 &&
    (checkout?.items ?? []).every((it) => it.nature === "virtual");

  return (
    <SiteLayout>
      <main className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6">
        <Button asChild variant="ghost" size="sm" className="-ml-2 mb-6 text-muted-foreground">
          <Link to="/cart"><ArrowLeft className="size-4" /> 返回购物车</Link>
        </Button>
        <header className="mb-6">
          <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">确认订单</p>
          <h1 className="mt-2 font-display text-3xl font-semibold text-foreground">核对并提交</h1>
        </header>

        {isLoading ? (
          <Skeleton className="h-72 rounded-xl" />
        ) : checkoutError ? (
          <div className="rounded-xl border border-destructive/20 bg-destructive/5 p-6 text-center">
            <p className="text-sm text-destructive">结算信息加载失败，请稍后重试</p>
            <Button variant="outline" size="sm" className="mt-3" onClick={() => window.location.reload()}>
              重新加载
            </Button>
          </div>
        ) : !checkout ? (
          <Skeleton className="h-72 rounded-xl" />
        ) : (
          <div className="space-y-5">
            <section className="rounded-xl border bg-card p-6">
              <p className="mb-3 flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">
                <MapPin className="size-4" /> 收货地址
              </p>
              {isVirtualOnly ? (
                <p className="text-sm text-muted-foreground">虚拟商品订单，支付后自动发货，无需收货地址。</p>
              ) : defaultAddr ? (
                <p className="text-sm text-foreground">
                  <span className="font-medium">{defaultAddr.name}</span> {defaultAddr.phone}
                  <span className="mt-1 block text-muted-foreground">{defaultAddr.province}{defaultAddr.city}{defaultAddr.district}{defaultAddr.detail}</span>
                  {defaultAddr.is_default && <span className="mt-2 inline-block rounded bg-clay/10 px-2 py-0.5 text-xs font-medium text-clay">默认地址</span>}
                </p>
              ) : (
                <p className="text-sm text-muted-foreground">
                  还没有收货地址
                  <Link to="/address" className="ml-2 font-medium text-clay underline">去添加</Link>
                </p>
              )}
            </section>

            <section className="rounded-xl border bg-card p-6">
              <p className="mb-4 font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">商品清单</p>
              <ul className="space-y-4">
                {(checkout.items ?? []).map((it) => (
                  <li key={it.goods_id} className="flex items-center gap-4">
                    {it.image ? (
                      <AppImage src={it.image} alt={it.name} className="size-16 rounded-xl object-cover" />
                    ) : (
                      <span className="flex size-16 items-center justify-center rounded-xl bg-surface text-muted-foreground">{it.name.slice(0, 1)}</span>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-medium text-foreground">{it.name}</p>
                      {it.spec_text && <p className="mt-0.5 text-xs text-muted-foreground">{it.spec_text}</p>}
                      <p className="mt-0.5 text-xs text-muted-foreground">× {it.count}</p>
                    </div>
                    <span className="text-sm font-medium text-foreground">{formatPrice(it.price)}</span>
                  </li>
                ))}
              </ul>
            </section>

            <section className="rounded-xl border bg-card p-6">
              <p className="mb-3 flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">
                <Receipt className="size-4" /> 支付方式
              </p>
              <p className="text-sm text-foreground">
                <span className="flex flex-wrap gap-2">
                  {PAY_CHANNELS.map((opt) => (
                    <button
                      key={opt.channel}
                      type="button"
                      onClick={() => setPayment(opt.channel)}
                      disabled={placeOrder.isPending}
                      className={cn(
                        "rounded border px-4 py-2 text-sm font-medium transition-colors disabled:opacity-50",
                        payment === opt.channel
                          ? "border-clay bg-clay/10 text-clay"
                          : "border-border bg-card text-muted-foreground hover:border-clay/40",
                      )}
                    >
                      {opt.label}
                    </button>
                  ))}
                </span>
              </p>
              {(checkout.payments ?? []).length > 0 && (
                <p className="mt-2 text-xs text-muted-foreground">
                  可用支付渠道：{(checkout.payments ?? []).map((p) => p.name).join(" / ")}
                </p>
              )}
              <div className="mt-4 space-y-1 border-t pt-4 text-sm">
                <p className="flex justify-between text-muted-foreground"><span>商品小计</span><span>{formatPrice(checkout.total_price)}</span></p>
                {!isVirtualOnly && (
                  <p className="flex justify-between text-muted-foreground"><span>运费</span><span>{checkout.shipping_amount > 0 ? formatPrice(checkout.shipping_amount) : "免运费"}</span></p>
                )}
                <p className="flex justify-between pt-2 text-base font-semibold text-foreground"><span>应付</span><span className="text-clay">{formatPrice(checkout.actual_price)}</span></p>
              </div>
            </section>

            <Button
              className="w-full rounded py-6 text-base"
              disabled={placeOrder.isPending}
              onClick={() => submitOrder()}
            >
              {placeOrder.isPending ? "提交中…" : `提交订单 · ${formatPrice(checkout.actual_price)}`}
            </Button>
          </div>
        )}
      </main>

      <PayQrDialog
        open={qrDialog.open}
        qrCode={qrDialog.qrCode}
        orderId={qrDialog.orderId}
        onClose={closeQrDialog}
        onSuccess={onQrPaySuccess}
      />
    </SiteLayout>
  );
}