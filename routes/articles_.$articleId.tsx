import { Link, createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft, CalendarDays, FileText } from "lucide-react";
import { fetchArticleDetail } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { SiteLayout } from "@/components/site/layout";
import { AppImage } from "@/components/system/app-image";

export const Route = createFileRoute("/articles_/$articleId")({
  component: ArticleDetail,
});

function ArticleDetail() {
  const { articleId } = Route.useParams();
  const { data: article, isLoading } = useQuery({
    queryKey: ["article", articleId],
    queryFn: () => fetchArticleDetail(Number(articleId)),
  });

  return (
    <SiteLayout>
      <div className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <Button asChild variant="ghost" className="mb-6 -ml-2 rounded text-muted-foreground">
          <Link to="/articles">
            <ArrowLeft className="size-4" />
            返回文章列表
          </Link>
        </Button>

        {isLoading ? (
          <Card className="h-80 rounded-xl">
            <CardContent />
          </Card>
        ) : !article ? (
          <Card className="rounded-xl py-16 text-center">
            <CardContent>
              <FileText className="mx-auto mb-4 size-10 text-muted-foreground/50" />
              <p className="font-display text-lg text-ink">文章不存在</p>
              <Button asChild variant="outline" className="mt-6 rounded">
                <Link to="/articles">返回列表</Link>
              </Button>
            </CardContent>
          </Card>
        ) : (
          <article>
            <p className="mb-2 inline-flex items-center gap-1.5 text-sm text-muted-foreground">
              <CalendarDays className="size-4" />
              {article.created_at || article.add_time || ""}
            </p>
            <h1 className="font-display text-3xl font-bold leading-tight tracking-tight text-ink sm:text-4xl">
              {article.title}
            </h1>
            {article.cover && (
              <div className="mt-6 aspect-[16/9] w-full overflow-hidden rounded-xl bg-surface">
                <AppImage
                  src={article.cover}
                  alt={article.title}
                  className="h-full w-full object-cover"
                />
              </div>
            )}
            <div className="prose-sm mt-6 text-foreground/90">
              {article.content ? (
                <p className="whitespace-pre-wrap leading-relaxed">{article.content}</p>
              ) : (
                <p className="text-muted-foreground">文章正文整理中。</p>
              )}
            </div>
          </article>
        )}
      </div>
    </SiteLayout>
  );
}
