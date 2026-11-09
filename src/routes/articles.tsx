import { Link, createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { CalendarDays, FileText, Newspaper } from "lucide-react";
import { fetchArticles } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { SiteLayout } from "@/components/site/layout";
import { AppImage } from "@/components/system/app-image";

export const Route = createFileRoute("/articles")({
  component: Articles,
});

function Articles() {
  const { data, isLoading } = useQuery({
    queryKey: ["articles"],
    queryFn: () => fetchArticles({ page: 1, limit: 20 }),
  });

  const list = data?.list ?? [];

  return (
    <SiteLayout>
      <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        {/* 模块头 */}
        <header className="mb-8">
          <p className="mb-2 inline-flex items-center gap-1.5 font-mono text-xs font-semibold uppercase tracking-widest text-clay">
            <Newspaper className="size-4" />
            内容
          </p>
          <h1 className="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
            小夏生活志
          </h1>
          <p className="mt-2 max-w-xl text-muted-foreground">
            生活方式、好物灵感与品牌故事，陪你发现更温暖的家。
          </p>
        </header>

        {isLoading ? (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {Array.from({ length: 6 }).map((_, i) => (
              <Skeleton key={i} className="h-64 rounded-xl" />
            ))}
          </div>
        ) : list.length === 0 ? (
          <Card className="rounded-xl border-dashed py-16 text-center">
            <CardContent>
              <FileText className="mx-auto mb-4 size-10 text-muted-foreground/50" />
              <p className="font-display text-lg text-ink">内容筹备中</p>
              <p className="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                文章与生活方式内容将陆续上线，敬请期待。
              </p>
              <Button asChild variant="outline" className="mt-6 rounded">
                <Link to="/products">先去逛逛好物</Link>
              </Button>
            </CardContent>
          </Card>
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {list.map((a) => (
              <Link
                key={a.id}
                to="/articles/$articleId"
                params={{ articleId: String(a.id) }}
                className="group overflow-hidden rounded-xl border border-border/70 bg-card transition-shadow hover:shadow-elegant"
              >
                <div className="aspect-[16/10] w-full overflow-hidden bg-surface">
                  {a.cover ? (
                    <AppImage
                      src={a.cover}
                      alt={a.title}
                      className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                  ) : (
                    <div className="grid h-full w-full place-items-center text-muted-foreground/40">
                      <FileText className="size-10" />
                    </div>
                  )}
                </div>
                <CardHeader>
                  <CardTitle className="font-display text-lg leading-snug text-ink group-hover:text-clay">
                    {a.title}
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {a.summary && (
                    <p className="line-clamp-2 text-sm text-muted-foreground">{a.summary}</p>
                  )}
                  <p className="mt-3 inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                    <CalendarDays className="size-3.5" />
                    {a.created_at || a.add_time || ""}
                  </p>
                </CardContent>
              </Link>
            ))}
          </div>
        )}
      </div>
    </SiteLayout>
  );
}
