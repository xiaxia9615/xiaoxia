import { Link } from "@tanstack/react-router";
import { PackageOpen, RotateCcw, ShieldCheck } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { toErrorMessage } from "@/lib/api";
import { AppImage } from "@/components/system/app-image";
import { useAftersales } from "../use-aftersales";

/** 售后申请页：业务逻辑在 useAftersales。 */
export function AftersalesPage() {
  const { aftersales, isLoading, isError, error, refetch, cancelMutation } = useAftersales();

  return (
    <SiteLayout>
      <main className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6">
        <header className="mb-6">
          <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">售后服务</p>
          <h1 className="mt-2 font-display text-3xl font-semibold text-foreground">售后申请</h1>
          <p className="mt-1 text-sm text-muted-foreground">查看你的退款、退货、换货申请进度。</p>
        </header>

        {isLoading ? (
          <Skeleton className="h-48 rounded-xl" />
        ) : isError ? (
          <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-20 text-center">
            <ShieldCheck className="size-10 text-muted-foreground/40" />
            <p className="mt-4 font-display text-xl text-foreground">售后记录加载失败</p>
            <p className="mt-1 text-sm text-muted-foreground">
              {toErrorMessage(error, "网络异常，请稍后重试")}
            </p>
            <Button className="mt-6 rounded px-6" onClick={() => void refetch()}>
              重新加载
            </Button>
          </div>
        ) : aftersales.length === 0 ? (
          <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-20 text-center">
            <ShieldCheck className="size-10 text-muted-foreground/40" />
            <p className="mt-4 font-display text-xl text-foreground">暂无售后申请</p>
            <p className="mt-1 text-sm text-muted-foreground">如需退款、退货或换货，可在订单详情中发起。</p>
            <Button asChild className="mt-6 rounded px-6">
              <Link to="/orders">查看订单</Link>
            </Button>
          </div>
        ) : (
          <ul className="space-y-4">
            {aftersales.map((a) => (
              <li key={a.id} className="flex items-center gap-4 rounded-xl border bg-card p-5">
                {a.image ? (
                  <AppImage src={a.image} alt={a.goods_name} className="size-16 rounded-xl object-cover" />
                ) : (
                  <span className="flex size-16 items-center justify-center rounded-xl bg-surface text-muted-foreground">
                    <PackageOpen className="size-6" />
                  </span>
                )}
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium text-foreground">{a.goods_name}</p>
                  <p className="mt-0.5 text-xs text-muted-foreground">
                    {a.type_name || a.type} · {a.reason}
                  </p>
                  <span className="mt-1 inline-block rounded bg-clay/10 px-2 py-0.5 text-xs font-medium text-clay">
                    {a.status_name || "处理中"}
                  </span>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  className="shrink-0 text-muted-foreground"
                  disabled={cancelMutation.isPending}
                  onClick={() => cancelMutation.mutate(a.id)}
                >
                  <RotateCcw className="size-4" /> 撤销
                </Button>
              </li>
            ))}
          </ul>
        )}
      </main>
    </SiteLayout>
  );
}
