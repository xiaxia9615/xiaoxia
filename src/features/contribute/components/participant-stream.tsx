import { HandHeart } from "lucide-react";
import type { ContributeItem } from "@/lib/api";

/** 参与流列表：只消费页面模型，不接触云表 DTO。 */
export function ParticipantStream({
  items,
  isLoading,
}: {
  items: ContributeItem[];
  isLoading: boolean;
}) {
  if (isLoading) {
    return (
      <p className="rounded border border-border bg-card/60 p-4 font-mono text-sm text-muted-foreground">
        加载参与记录中…
      </p>
    );
  }
  if (items.length === 0) {
    return (
      <p className="rounded border border-border bg-card/60 p-4 font-mono text-sm text-muted-foreground">
        还没有参与记录，成为第一位伙伴吧。
      </p>
    );
  }
  return (
    <div className="grid gap-4">
      {items.map((entry, i) => (
        <div
          key={entry.id}
          className="flex animate-fade-up items-center gap-4 rounded border border-border bg-card/60 p-4 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:border-clay/40 hover:shadow-lift"
          style={{ animationDelay: `${Math.min(i, 6) * 0.06}s` }}
        >
          <span className="grid size-11 shrink-0 place-items-center rounded bg-terminal text-clay shadow-scan">
            <HandHeart className="size-5" />
          </span>
          <div className="min-w-0 flex-1">
            <div className="flex items-center gap-2">
              <p className="truncate text-sm font-semibold text-ink">{entry.name}</p>
              <span className="rounded bg-ok/10 px-1.5 py-0.5 font-mono text-[10px] text-ok">
                助力 ×{entry.count}
              </span>
            </div>
            <p className="mt-0.5 truncate text-xs text-muted-foreground">
              {entry.note || "我加入开放计划啦"}
            </p>
          </div>
          <span className="shrink-0 font-mono text-xs text-muted-foreground">
            {formatCreatedAt(entry.createdAt)}
          </span>
        </div>
      ))}
    </div>
  );
}

function formatCreatedAt(value: string): string {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  return date.toLocaleString("zh-CN", {
    month: "numeric",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}
