import { Link, createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { MessageCircleHeart, Search } from "lucide-react";
import { useMemo, useState } from "react";
import { ProductCard } from "@/features/catalog/product-card";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { CATEGORIES, type CategoryId } from "@/data/products";
import { fetchProducts } from "@/lib/api";
import { cn } from "@/lib/utils";

interface ProductsSearch {
  category?: CategoryId;
}

export const Route = createFileRoute("/products")({
  validateSearch: (search: Record<string, unknown>): ProductsSearch => ({
    category:
      search.category === "drink" ||
      search.category === "home" ||
      search.category === "decor"
        ? search.category
        : undefined,
  }),
  component: ProductsPage,
});

function ProductsPage() {
  const { category } = Route.useSearch();
  const navigate = Route.useNavigate();
  const [query, setQuery] = useState("");

  const { data, isFetching } = useQuery({
    queryKey: ["products", category ?? "all"],
    queryFn: () => fetchProducts({ category }),
  });

  const selectCategory = (id: CategoryId) => {
    void navigate({
      search: { category: id === "all" ? undefined : id },
    });
  };

  const filtered = useMemo(() => {
    const list = data?.list ?? [];
    const q = query.trim().toLowerCase();
    if (!q) return list;
    return list.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.description.toLowerCase().includes(q) ||
        p.tags.some((t) => t.toLowerCase().includes(q)),
    );
  }, [data, query]);

  return (
    <SiteLayout>
      <section className="mx-auto max-w-6xl px-4 pb-8 pt-12 sm:px-6">
        <p className="font-mono text-xs font-semibold uppercase tracking-widest text-clay">全部好物</p>
        <h1 className="mt-1 font-display text-3xl font-bold text-ink sm:text-4xl">
          用心挑选的生活好物
        </h1>
        <p className="mt-2 max-w-xl text-sm leading-6 text-muted-foreground">
          从器物到织物，每一件都经过认真甄选。也可以问问 AI 导购，让它帮你拿主意。
        </p>

        <div className="mt-8 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex flex-wrap gap-2">
            {CATEGORIES.map((c) => (
              <button
                key={c.id}
                onClick={() => selectCategory(c.id)}
                className={cn(
                  "rounded border px-4 py-2 text-sm font-medium transition-colors",
                  category === c.id
                    ? "border-clay bg-clay text-clay-foreground"
                    : "border-border bg-card text-muted-foreground hover:border-clay/40 hover:text-clay",
                )}
              >
                {c.label}
              </button>
            ))}
          </div>

          <div className="relative lg:w-72">
            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="搜索好物、材质或标签"
              className="rounded pl-9"
            />
          </div>
        </div>

        <p className="mt-6 text-sm text-muted-foreground">
          共 {isFetching ? "…" : filtered.length} 件好物
        </p>
      </section>

      <section className="mx-auto max-w-6xl px-4 sm:px-6">
        {isFetching ? (
          <div className="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
            {Array.from({ length: 8 }).map((_, i) => (
              <div key={i} className="overflow-hidden rounded-xl border border-border/70 bg-card">
                <Skeleton className="aspect-square w-full rounded-none" />
                <div className="space-y-2 p-5">
                  <Skeleton className="h-3 w-1/3" />
                  <Skeleton className="h-4 w-3/4" />
                  <Skeleton className="h-4 w-1/2" />
                </div>
              </div>
            ))}
          </div>
        ) : filtered.length > 0 ? (
          <div className="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
            {filtered.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        ) : (
          <div className="flex flex-col items-center rounded-xl border border-dashed border-border bg-card px-6 py-20 text-center">
            <Search className="size-8 text-muted-foreground" />
            <h2 className="mt-4 font-display text-lg font-semibold text-ink">
              没有找到匹配的好物
            </h2>
            <p className="mt-1 max-w-xs text-sm text-muted-foreground">
              换个关键词，或让 AI 导购助手帮你找找看。
            </p>
            <Button asChild variant="outline" className="mt-6 rounded">
              <Link to="/assistant">
                <MessageCircleHeart className="size-4" />
                问问 AI 导购
              </Link>
            </Button>
          </div>
        )}
      </section>
    </SiteLayout>
  );
}
