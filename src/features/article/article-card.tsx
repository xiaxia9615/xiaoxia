import { Link } from "@tanstack/react-router";
import { CalendarDays, FileText } from "lucide-react";
import { CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { AppImage } from "@/components/system/app-image";
import type { Article } from "@/lib/api";

/** 文章列表卡片：封面 + 标题 + 摘要 + 日期。 */
export function ArticleCard({ article }: { article: Article }) {
  const a = article;
  return (
    <Link
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
  );
}
