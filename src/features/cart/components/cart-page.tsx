import { Link } from "@tanstack/react-router";
import { MapPin, ShoppingBag, Trash2 } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { toErrorMessage } from "@/lib/api";
import { AppImage } from "@/components/system/app-image";
import { useCartPage } from "../use-cart-page";

/** 购物车页：业务逻辑在 useCartPage，组件只负责渲染。 */
export function CartPage({ loggedIn }: { loggedIn: boolean }) {
  const {
    items,
    isError,
    error,
    refetch,
    subtotal,
    shipping,
    total,
    defaultAddress,
    removeItem,
    checkout,
    formatPrice,
  } = useCartPage(loggedIn);

  return (
    <SiteLayout>
      <section className="mx-auto max-w-6xl px-4 pb-10 pt-12 sm:px-6">
        <h1 className="font-display text-3xl font-bold text-ink">购物车</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          共 {items.length} 种好物
        </p>

        <div className="mt-8 grid gap-8 lg:grid-cols-[1fr_340px]">
          <div className="space-y-4">
            {loggedIn && isError ? (
              <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-16 text-center">
                <ShoppingBag className="size-10 text-muted-foreground/40" />
                <p className="mt-4 font-display text-xl text-foreground">购物车加载失败</p>
                <p className="mt-1 text-sm text-muted-foreground">
                  {toErrorMessage(error, "网络异常，请稍后重试")}
                </p>
                <Button className="mt-6 rounded px-6" onClick={() => void refetch()}>重新加载</Button>
              </div>
            ) : items.map((item) => {
              const qty = item.quantity;
              const price = item.price;
              const name = item.goods_name;
              const image = item.image;

              return (
                <div
                  key={item.key}
                  className="flex gap-4 rounded-xl border border-border/70 bg-card p-4 shadow-soft"
                >
                  <div className="shrink-0 overflow-hidden rounded-lg bg-surface">
                    {image ? (
                      <AppImage
                        src={image}
                        alt={name}
                        className="size-20 object-cover sm:size-24"
                      />
                    ) : (
                      <span className="grid size-20 place-items-center bg-surface text-muted-foreground sm:size-24">
                        <ShoppingBag className="size-6" />
                      </span>
                    )}
                  </div>

                  <div className="flex min-w-0 flex-1 flex-col">
                    <div className="flex items-start justify-between gap-2">
                      <div className="min-w-0">
                        <p className="truncate font-display text-base font-semibold text-ink">
                          {name}
                        </p>
                      </div>
                      {loggedIn && item.id != null && (
                        <button
                          onClick={() => removeItem(item)}
                          className="grid size-8 shrink-0 place-items-center rounded text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"
                          aria-label={`移除 ${name}`}
                        >
                          <Trash2 className="size-4" />
                        </button>
                      )}
                    </div>

                    <div className="mt-auto flex items-end justify-between pt-3">
                      <span className="text-sm text-muted-foreground">
                        数量 ×{qty}
                      </span>
                      <p className="font-display text-lg font-bold text-clay">
                        {formatPrice(price * qty)}
                      </p>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          <aside className="h-fit rounded-xl border border-border/70 bg-card p-6 shadow-card lg:sticky lg:top-24">
            <h2 className="font-display text-lg font-semibold text-ink">订单摘要</h2>
            <dl className="mt-4 space-y-3 text-sm">
              <div className="flex justify-between">
                <dt className="text-muted-foreground">商品小计</dt>
                <dd className="font-medium text-ink">{formatPrice(subtotal)}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-muted-foreground">运费</dt>
                <dd className="font-medium text-ink">
                  {shipping === 0 ? "免运费" : formatPrice(shipping)}
                </dd>
              </div>
              <div className="flex justify-between border-t border-border pt-3">
                <dt className="font-semibold text-ink">合计</dt>
                <dd className="font-display text-xl font-black text-clay">
                  {formatPrice(total)}
                </dd>
              </div>
            </dl>
            {!loggedIn && (
              <p className="mt-3 rounded-lg bg-clay/10 px-3 py-2 text-xs text-clay">
                登录后即可结算下单
              </p>
            )}
            {loggedIn && defaultAddress && (
              <div className="mt-3 rounded-lg bg-background px-3 py-2.5">
                <div className="flex items-start gap-2">
                  <MapPin className="mt-0.5 size-4 shrink-0 text-clay" />
                  <div className="min-w-0 text-xs">
                    <p className="font-medium text-ink">{defaultAddress?.name}</p>
                    <p className="mt-0.5 truncate text-muted-foreground">
                      {defaultAddress?.province} {defaultAddress?.city} {defaultAddress?.district}{" "}
                      {defaultAddress?.detail}
                    </p>
                  </div>
                </div>
                <Link
                  to="/address"
                  className="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-clay hover:underline"
                >
                  管理地址
                </Link>
              </div>
            )}
            <Button
              size="lg"
              className="mt-5 w-full rounded"
              onClick={() => void checkout()}
              disabled={false}
            >
              "去结算"
            </Button>
            <Button
              asChild
              variant="ghost"
              className="mt-2 w-full rounded text-muted-foreground"
            >
              <Link to="/products">继续选购</Link>
            </Button>
          </aside>
        </div>
      </section>
    </SiteLayout>
  );
}