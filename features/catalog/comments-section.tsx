import { useQuery } from "@tanstack/react-query";
import { fetchComments, type CommentItem } from "@/lib/api";
import { AppImage } from "@/components/system/app-image";

/** 商品详情页 · 买家评价区。无评价时展示引导空态。 */
export function CommentsSection({ goodsId }: { goodsId: number }) {
  const { data: comments = [] } = useQuery<CommentItem[]>({
    queryKey: ["comments", goodsId],
    queryFn: () => fetchComments(goodsId),
  });
  if (comments.length === 0) {
    return (
      <section className="mx-auto w-full max-w-6xl px-4 pb-16 sm:px-6">
        <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">买家评价</p>
        <p className="mt-3 rounded-lg border border-dashed bg-card/60 py-10 text-center text-sm text-muted-foreground">
          还没有评价，下单后欢迎分享你的使用感受。
        </p>
      </section>
    );
  }
  return (
    <section className="mx-auto w-full max-w-6xl px-4 pb-16 sm:px-6">
      <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">买家评价</p>
      <div className="mt-4 grid gap-4 md:grid-cols-2">
        {comments.map((c) => (
          <article key={c.id} className="rounded-lg border bg-card p-5">
            <div className="flex items-center gap-3">
              {c.avatar ? (
                <AppImage src={c.avatar} alt={c.user_name} className="size-9 rounded object-cover" />
              ) : (
                <span className="flex size-9 items-center justify-center rounded bg-surface text-sm text-muted-foreground">
                  {(c.user_name || "用").slice(0, 1)}
                </span>
              )}
              <div>
                <p className="text-sm font-medium text-foreground">{c.user_name || "匿名用户"}</p>
                {typeof c.score === "number" && (
                  <p className="text-xs text-clay">{"★".repeat(Math.max(1, Math.min(5, c.score)))}</p>
                )}
              </div>
            </div>
            <p className="mt-3 text-sm leading-relaxed text-foreground">{c.content}</p>
            {c.images && c.images.length > 0 && (
              <div className="mt-3 flex flex-wrap gap-2">
                {c.images.map((img, i) => (
                  <AppImage key={i} src={img} alt="评价图片" className="size-16 rounded-lg object-cover" />
                ))}
              </div>
            )}
            {c.add_time && <p className="mt-3 text-xs text-muted-foreground/70">{c.add_time}</p>}
          </article>
        ))}
      </div>
    </section>
  );
}
