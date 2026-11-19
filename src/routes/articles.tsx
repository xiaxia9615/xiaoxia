import { Link, createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { FileText, Newspaper } from "lucide-react";
import { fetchArticles } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { SiteLayout } from "@/components/site/layout";
import { ArticleCard } from "@/features/article/article-card";

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
              <ArticleCard key={a.id} article={a} />
            ))}
          </div>
        )}
      </div>
    </SiteLayout>
  );
}
