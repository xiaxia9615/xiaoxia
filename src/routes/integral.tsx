import { requireAuth } from "@/lib/auth/guard";
import { createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { Coins, Wallet, Snowflake } from "lucide-react";
import { useAuth } from "@/features/account/auth-provider";
import { useRequireLogin } from "@/features/account/use-require-login";
import { SiteLayout } from "@/components/site/layout";
import { Skeleton } from "@/components/ui/skeleton";
import { fetchIntegral } from "@/lib/api";

export const Route = createFileRoute("/integral")({
  beforeLoad: requireAuth,
  component: Integral,
});

function Integral() {
  const { user } = useAuth();
  const { data, isLoading } = useQuery({
    queryKey: ["integral"],
    queryFn: fetchIntegral,
    enabled: Boolean(user),
  });

  const ready = useRequireLogin("/integral");
  if (!ready) {
    return null;
  }

  return (
    <SiteLayout>
      <main className="mx-auto w-full max-w-2xl px-4 py-10 sm:px-6">
        <header className="mb-6">
          <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">我的资产</p>
          <h1 className="mt-2 font-display text-3xl font-semibold text-foreground">积分与余额</h1>
        </header>

        {isLoading ? (
          <Skeleton className="h-48 rounded-xl" />
        ) : data ? (
          <div className="space-y-4">
            <div className="grid grid-cols-3 gap-4">
              <div className="rounded-xl border bg-card p-5">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Wallet className="h-4 w-4" />
                  <span className="text-xs">余额</span>
                </div>
                <p className="mt-2 font-display text-2xl font-semibold text-foreground">
                  ¥{data.balance.toFixed(2)}
                </p>
              </div>

              <div className="rounded-xl border bg-card p-5">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Snowflake className="h-4 w-4" />
                  <span className="text-xs">冻结</span>
                </div>
                <p className="mt-2 font-display text-2xl font-semibold text-foreground">
                  ¥{data.frozen_balance.toFixed(2)}
                </p>
              </div>

              <div className="rounded-xl border bg-card p-5">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Coins className="h-4 w-4" />
                  <span className="text-xs">积分</span>
                </div>
                <p className="mt-2 font-display text-2xl font-semibold text-foreground">
                  {data.points}
                </p>
              </div>
            </div>

            <div className="rounded-xl border border-dashed bg-card/60 py-12 text-center">
              <Coins className="mx-auto size-8 text-muted-foreground/40" />
              <p className="mt-3 text-sm text-muted-foreground">
                积分明细记录功能开发中，敬请期待。
              </p>
            </div>
          </div>
        ) : (
          <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-20 text-center">
            <Coins className="size-10 text-muted-foreground/40" />
            <p className="mt-4 font-display text-xl text-foreground">暂无数据</p>
          </div>
        )}
      </main>
    </SiteLayout>
  );
}
