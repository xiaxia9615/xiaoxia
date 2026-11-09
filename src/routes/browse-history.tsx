import { createFileRoute, Link } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { Clock, History } from "lucide-react";
import { useAuth } from "@/features/account/auth-provider";
import { useRequireLogin } from "@/features/account/use-require-login";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { fetchBrowseHistory } from "@/lib/api";
import { formatPrice } from "@/data/products";
import { AppImage } from "@/components/system/app-image";

export const Route = createFileRoute("/browse-history")({
  component: BrowseHistory,
});

function BrowseHistory() {
  const { user } = useAuth();

  const { data: items = [], isLoading } = useQuery({
    queryKey: ["browse-history"],
    queryFn: fetchBrowseHistory,
    enabled: Boolean(user),
  });

  const ready = useRequireLogin("/browse-history");
  if (!ready) {
    return null;
  }

  return (
    <SiteLayout>
      <main className="mx-auto w-full max-w-3xl px-4 py-10 sm:px-6">
        <header className="mb-6">
          <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">浏览记录</p>
          <h1 className="mt-2 font-display text-3xl font-semibold text-foreground">最近看过</h1>
        </header>

        {isLoading ? (
          <Skeleton className="h-48 rounded-xl" />
        ) : items.length === 0 ? (
          <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-20 text-center">
            <History className="size-10 text-muted-foreground/40" />
            <p className="mt-4 font-display text-xl text-foreground">暂无浏览记录</p>
            <p className="mt-1 text-sm text-muted-foreground">浏览过的商品会出现在这里。</p>
            <Button asChild className="mt-6 rounded px-6">
              <Link to="/products">去逛逛</Link>
            </Button>
          </div>
        ) : (
          <ul className="grid gap-4 sm:grid-cols-2">
            {items.map((it) => (
              <li key={it.id}>
                <Link to={`/products/${it.goods_id}`} className="flex items-center gap-4 rounded-xl border bg-card p-4 transition hover:shadow-soft">
                  {it.image ? (
                    <AppImage src={it.image} alt={it.name} className="size-16 rounded-xl object-cover" />
                  ) : (
                    <span className="flex size-16 items-center justify-center rounded-xl bg-surface text-muted-foreground"><Clock className="size-5" /></span>
                  )}
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium text-foreground">{it.name}</p>
                    {typeof it.price === "number" && <p className="mt-1 text-clay">{formatPrice(it.price)}</p>}
                  </div>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </main>
    </SiteLayout>
  );
}