import { Link, createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { Heart, ShoppingBag } from "lucide-react";
import { useAuth } from "@/features/account/auth-provider";
import { useRequireLogin } from "@/features/account/use-require-login";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { fetchFavorites, setFavorite } from "@/lib/api";
import { formatPrice } from "@/data/products";
import { AppImage } from "@/components/system/app-image";

export const Route = createFileRoute("/favorites")({
  component: Favorites,
});

function Favorites() {
  const { user } = useAuth();

  const { data: favorites = [], isLoading, refetch } = useQuery({
    queryKey: ["favorites"],
    queryFn: () => fetchFavorites(),
    enabled: Boolean(user),
  });

  const ready = useRequireLogin("/favorites");
  if (!ready) {
    return null;
  }

  const removeFav = async (goodsId: number) => {
    await setFavorite(goodsId, false);
    refetch();
  };

  return (
    <SiteLayout>
      <main className="mx-auto w-full max-w-5xl px-4 py-10 sm:px-6">
        <header className="mb-8">
          <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">我的收藏</p>
          <h1 className="mt-2 font-display text-3xl font-semibold text-foreground">心仪好物</h1>
          <p className="mt-1 text-sm text-muted-foreground">把喜欢的都收进来，随时回来看看。</p>
        </header>

        {isLoading ? (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: 3 }).map((_, i) => (
              <Skeleton key={i} className="h-56 rounded-xl" />
            ))}
          </div>
        ) : favorites.length === 0 ? (
          <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-20 text-center">
            <Heart className="size-10 text-muted-foreground/40" />
            <p className="mt-4 font-display text-xl text-foreground">还没有收藏</p>
            <p className="mt-1 text-sm text-muted-foreground">逛逛商品，把喜欢的收藏起来吧。</p>
            <Button asChild className="mt-6 rounded px-6">
              <Link to="/products">
                <ShoppingBag className="size-4" /> 去逛逛
              </Link>
            </Button>
          </div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {favorites.map((f) => (
              <article key={f.goods_id} className="overflow-hidden rounded-xl border bg-card">
                <Link to={`/products/${f.goods_id}`} className="block">
                  {f.image ? (
                    <AppImage src={f.image} alt={f.name} className="aspect-square w-full object-cover" />
                  ) : (
                    <div className="flex aspect-square items-center justify-center bg-surface text-muted-foreground">
                      {f.name}
                    </div>
                  )}
                </Link>
                <div className="p-4">
                  <Link to={`/products/${f.goods_id}`} className="font-medium text-foreground line-clamp-1">
                    {f.name}
                  </Link>
                  <div className="mt-2 flex items-center justify-between">
                    <span className="text-clay">
                      {typeof f.price === "number" ? formatPrice(f.price) : ""}
                    </span>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="text-muted-foreground"
                      onClick={() => removeFav(f.goods_id)}
                    >
                      <Heart className="mr-1 size-4 fill-clay text-clay" /> 取消
                    </Button>
                  </div>
                </div>
              </article>
            ))}
          </div>
        )}
      </main>
    </SiteLayout>
  );
}