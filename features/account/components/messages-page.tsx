import { Bell, CheckCheck, Inbox } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { toErrorMessage } from "@/lib/api";
import { useMessages } from "../use-messages";

/** 通知页：业务逻辑在 useMessages。 */
export function MessagesPage() {
  const { messages, unreadCount, isLoading, isError, error, refetch, readMutation } =
    useMessages();

  return (
    <SiteLayout>
      <main className="mx-auto w-full max-w-2xl px-4 py-10 sm:px-6">
        <header className="mb-6 flex items-end justify-between">
          <div>
            <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">消息中心</p>
            <h1 className="mt-2 font-display text-3xl font-semibold text-foreground">通知</h1>
          </div>
          {unreadCount > 0 && (
            <Button
              variant="outline"
              size="sm"
              className="rounded"
              disabled={readMutation.isPending}
              onClick={() => readMutation.mutate()}
            >
              <CheckCheck className="mr-1 size-4" /> 全部已读
            </Button>
          )}
        </header>

        {isLoading ? (
          <div className="space-y-3">
            {Array.from({ length: 4 }).map((_, i) => (
              <Skeleton key={i} className="h-20 rounded-lg" />
            ))}
          </div>
        ) : isError ? (
          <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-20 text-center">
            <Inbox className="size-10 text-muted-foreground/40" />
            <p className="mt-4 font-display text-xl text-foreground">消息加载失败</p>
            <p className="mt-1 text-sm text-muted-foreground">
              {toErrorMessage(error, "网络异常，请稍后重试")}
            </p>
            <Button className="mt-6 rounded px-6" onClick={() => void refetch()}>
              重新加载
            </Button>
          </div>
        ) : messages.length === 0 ? (
          <div className="flex flex-col items-center rounded-xl border border-dashed bg-card/60 py-20 text-center">
            <Inbox className="size-10 text-muted-foreground/40" />
            <p className="mt-4 font-display text-xl text-foreground">暂无消息</p>
            <p className="mt-1 text-sm text-muted-foreground">有新通知时会在这里提醒你。</p>
          </div>
        ) : (
          <ul className="space-y-3">
            {messages.map((m) => (
              <li
                key={m.id}
                className={`flex gap-3 rounded-lg border bg-card p-4 ${!m.is_read ? "border-clay/30" : ""}`}
              >
                <span
                  className={`mt-0.5 flex size-9 shrink-0 items-center justify-center rounded ${
                    m.is_read ? "bg-surface text-muted-foreground" : "bg-clay text-clay-foreground"
                  }`}
                >
                  <Bell className="size-4" />
                </span>
                <div className="min-w-0 flex-1">
                  <div className="flex items-center justify-between gap-2">
                    <h3 className="truncate text-sm font-medium text-foreground">{m.title}</h3>
                    {!m.is_read && (
                      <span className="shrink-0 rounded bg-clay px-2 py-0.5 text-[10px] font-semibold text-clay-foreground">
                        新
                      </span>
                    )}
                  </div>
                  <p className="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-muted-foreground">
                    {m.content}
                  </p>
                  {m.add_time && (
                    <p className="mt-2 text-xs text-muted-foreground/70">{m.add_time}</p>
                  )}
                </div>
              </li>
            ))}
          </ul>
        )}
      </main>
    </SiteLayout>
  );
}
