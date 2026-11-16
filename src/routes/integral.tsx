import { requireAuth } from "@/lib/auth/guard";
import { createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { Coins } from "lucide-react";
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

  const { data: records = [], isLoading } = useQuery({
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
          <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">我的积分</p>
          <h1 className="mt-2 font-display text-3xl font-semibold text-foreground">积分明细</h1>
        </header>

        {isLoading ? (
          <Skeleton className="h-48 rounded-xl" />
        ) : records.length === 0 ? (
          <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-20 text-center">
            <Coins className="size-10 text-muted-foreground/40" />
            <p className="mt-4 font-display text-xl text-foreground">暂无积分记录</p>
            <p className="mt-1 text-sm text-muted-foreground">购物、签到等获得的积分会记录在这里。</p>
          </div>
        ) : (
          <ul className="space-y-3">
            {records.map((r) => (
              <li key={r.id} className="flex items-center justify-between rounded-lg border bg-card p-4">
                <div>
                  <p className="text-sm font-medium text-foreground">{r.change_type || r.remark || "积分变动"}</p>
                  {r.add_time && <p className="mt-0.5 text-xs text-muted-foreground">{r.add_time}</p>}
                </div>
                <span className={`text-sm font-semibold ${r.integral >= 0 ? "text-clay" : "text-muted-foreground"}`}>
                  {r.integral >= 0 ? "+" : ""}{r.integral}
                </span>
              </li>
            ))}
          </ul>
        )}
      </main>
    </SiteLayout>
  );
}