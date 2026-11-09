import { Link } from "@tanstack/react-router";
import { CheckCircle2, ChevronRight, CircleDashed, Loader2, Play, Terminal, TriangleAlert, XCircle } from "lucide-react";
import { useNavigate } from "@tanstack/react-router";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { cn } from "@/lib/utils";
import type { CheckStatus } from "../types";
import { usePaySelfCheck } from "../use-pay-self-check";

function statusIcon(status: CheckStatus) {
  switch (status) {
    case "idle":
      return <CircleDashed className="size-4.5 text-muted-foreground" />;
    case "running":
      return <Loader2 className="size-4.5 animate-spin text-clay" />;
    case "pass":
      return <CheckCircle2 className="size-4.5 text-ok" />;
    case "warn":
      return <TriangleAlert className="size-4.5 text-warn" />;
    case "fail":
      return <XCircle className="size-4.5 text-destructive" />;
  }
}

/** 支付自检终端页：状态机在 usePaySelfCheck，组件只负责渲染。 */
export function PaySelfCheckPage({
  userLabel,
  authLoading,
  needLogin,
}: {
  userLabel: string | null;
  authLoading: boolean;
  needLogin: boolean;
}) {
  const navigate = useNavigate();
  const { steps, logs, running, done, summary, logRef, runCheck } = usePaySelfCheck(
    userLabel,
    authLoading,
  );

  return (
    <SiteLayout>
      <div className="mx-auto max-w-3xl px-4 pb-10 pt-12 sm:px-6">
        {needLogin ? (
          <div className="rounded-xl border border-border/70 bg-card p-8 shadow-soft">
            <Terminal className="size-7 text-clay" />
            <h1 className="mt-4 font-display text-3xl font-bold text-ink">支付自检终端</h1>
            <p className="mt-3 text-muted-foreground">请先登录后再执行支付链路自检。</p>
            <Button asChild className="mt-5 rounded bg-clay text-clay-foreground">
              <Link to="/login">去登录</Link>
            </Button>
          </div>
        ) : authLoading ? (
          <div className="space-y-4">
            <Skeleton className="h-10 w-56" />
            <Skeleton className="h-64 w-full rounded-xl" />
          </div>
        ) : (
          <div className="overflow-hidden rounded-xl border border-border/70 bg-card shadow-soft">
            <div className="flex flex-wrap items-center justify-between gap-4 border-b border-border/70 p-5">
              <div className="flex items-center gap-3">
                <span className="grid size-10 place-items-center rounded bg-terminal text-clay shadow-scan">
                  <Terminal className="size-5" />
                </span>
                <div>
                  <h1 className="font-display text-2xl font-bold leading-tight text-ink">支付自检终端</h1>
                  <p className="text-sm text-muted-foreground">一键检测支付链路各环节运行状态</p>
                </div>
              </div>
              <Button
                onClick={runCheck}
                disabled={running}
                className="rounded bg-clay text-clay-foreground hover:bg-clay/90"
              >
                {running ? <Loader2 className="size-4 animate-spin" /> : <Play className="size-4" />}
                {running ? "自检进行中" : done ? "重新自检" : "开始自检"}
              </Button>
            </div>

            <ol className="divide-y divide-border/60">
              {steps.map((step, i) => (
                <li key={step.key} className="flex items-center gap-3 px-5 py-4">
                  <span className="w-5 shrink-0 text-right font-mono text-xs text-muted-foreground">
                    {String(i + 1).padStart(2, "0")}
                  </span>
                  {statusIcon(step.status)}
                  <div className="min-w-0 flex-1">
                    <div className="text-sm font-semibold text-ink">{step.title}</div>
                    <div className="truncate text-xs text-muted-foreground" title={step.detail}>
                      {step.detail}
                    </div>
                  </div>
                  {typeof step.ms === "number" && step.status !== "idle" && (
                    <span className="shrink-0 font-mono text-xs text-muted-foreground">{step.ms}ms</span>
                  )}
                  <ChevronRight className="size-4 shrink-0 text-muted-foreground/50" />
                </li>
              ))}
            </ol>

            <div className="border-t border-border/70 p-5">
              <div
                ref={logRef}
                className="h-44 overflow-y-auto rounded-lg bg-terminal p-4 font-mono text-xs leading-relaxed text-terminal-foreground border border-border/70"
                aria-live="polite"
              >
                {logs.length === 0 ? (
                  <p className="text-muted-foreground/60">$ 等待指令 —— 点击「开始自检」执行支付链路诊断</p>
                ) : (
                  logs.map((l, i) => (
                    <p key={i} className="whitespace-pre-wrap">
                      <span className="text-muted-foreground/60">[{l.time}]</span>{" "}
                      <span
                        className={cn(
                          l.level === "ok" && "text-ok",
                          l.level === "warn" && "text-warn",
                          l.level === "err" && "text-red-400",
                        )}
                      >
                        {l.text}
                      </span>
                    </p>
                  ))
                )}
              </div>

              {summary && (
                <div
                  role="status"
                  className={cn(
                    "mt-4 flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-medium",
                    summary.tone === "pass" && "bg-ok/10 text-ok",
                    summary.tone === "warn" && "bg-warn/10 text-warn",
                    summary.tone === "fail" && "bg-destructive/10 text-destructive",
                  )}
                >
                  {summary.tone === "pass" ? (
                    <CheckCircle2 className="size-4.5" />
                  ) : (
                    <TriangleAlert className="size-4.5" />
                  )}
                  {summary.text}
                  {steps[0].status === "fail" && (
                    <Button
                      variant="outline"
                      size="sm"
                      className="ml-auto rounded"
                      onClick={() => void navigate({ to: "/login" })}
                    >
                      去登录
                    </Button>
                  )}
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </SiteLayout>
  );
}