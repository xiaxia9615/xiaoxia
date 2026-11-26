import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { Loader2, RefreshCw, AlertCircle, CheckCircle, XCircle } from "lucide-react";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  getSystemHealth,
  getQueueStats,
  retryFailedJob,
  getSystemBackups,
  getSystemLogs,
  type SystemHealth,
  type FailedJobItem,
} from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

function HealthDot({ ok }: { ok: boolean }) {
  return ok ? (
    <CheckCircle className="h-4 w-4 text-emerald-400" />
  ) : (
    <XCircle className="h-4 w-4 text-red-400" />
  );
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function SystemPage() {
  const queryClient = useQueryClient();
  const [expandedJob, setExpandedJob] = useState<number | null>(null);

  const { data: health, isLoading: healthLoading } = useQuery({
    queryKey: ["admin-system-health"],
    queryFn: () => getSystemHealth(),
    refetchInterval: 30000,
  });

  const { data: queue, isLoading: queueLoading } = useQuery({
    queryKey: ["admin-system-queue"],
    queryFn: () => getQueueStats(),
    refetchInterval: 15000,
  });

  const { data: backups } = useQuery({
    queryKey: ["admin-system-backups"],
    queryFn: () => getSystemBackups(),
  });

  const { data: logs } = useQuery({
    queryKey: ["admin-system-logs"],
    queryFn: () => getSystemLogs(),
  });

  const retryMutation = useMutation({
    mutationFn: (uuid: string) => retryFailedJob(uuid),
    onSuccess: () => {
      toast.success("重试已提交", { description: "失败任务已重新入队。" });
      queryClient.invalidateQueries({ queryKey: ["admin-system-queue"] });
    },
    onError: (err) => {
      toast.error("重试失败", { description: toErrorMessage(err) });
    },
  });

  const healthItems: Array<{ label: string; value: boolean | string }> = health
    ? [
        { label: "数据库", value: health.db },
        { label: "缓存", value: health.cache },
        { label: "Nginx", value: health.nginx },
        { label: "PHP-FPM", value: health["php-fpm"] },
        { label: "队列 Worker", value: health.worker },
      ]
    : [];

  return (
    <AdminPageLayout title="系统设置" description="系统健康、队列监控、备份与日志">
      <div className="space-y-6">
        {/* 健康检查 */}
        <section>
          <h3 className="mb-3 text-sm font-medium text-slate-300">服务健康</h3>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
            {healthLoading ? (
              <div className="col-span-5 flex items-center gap-2 text-slate-500">
                <Loader2 className="h-4 w-4 animate-spin" /> 加载中…
              </div>
            ) : (
              healthItems.map((item) => (
                <div
                  key={item.label}
                  className="flex items-center gap-2 rounded-lg border border-white/10 bg-black/40 px-4 py-3"
                >
                  {typeof item.value === "boolean" ? (
                    <HealthDot ok={item.value} />
                  ) : (
                    <AlertCircle
                      className={item.value === "ok" ? "h-4 w-4 text-emerald-400" : "h-4 w-4 text-amber-400"}
                    />
                  )}
                  <div>
                    <div className="text-xs text-slate-500">{item.label}</div>
                    <div className="text-sm text-slate-200">
                      {typeof item.value === "boolean"
                        ? item.value
                          ? "正常"
                          : "异常"
                        : item.value}
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </section>

        {/* 队列监控 */}
        <section>
          <h3 className="mb-3 text-sm font-medium text-slate-300">队列监控</h3>
          {queueLoading ? (
            <div className="flex items-center gap-2 text-slate-500">
              <Loader2 className="h-4 w-4 animate-spin" /> 加载中…
            </div>
          ) : queue ? (
            <div className="space-y-3">
              <div className="flex gap-6 rounded-lg border border-white/10 bg-black/40 px-6 py-3">
                <div>
                  <span className="text-sm text-slate-500">待处理任务</span>
                  <span className="ml-2 font-mono text-lg text-slate-200">
                    {queue.pending}
                  </span>
                </div>
                <div>
                  <span className="text-sm text-slate-500">失败任务</span>
                  <span
                    className={queue.failed.length > 0 ? "ml-2 font-mono text-lg text-red-400" : "ml-2 font-mono text-lg text-slate-200"}
                  >
                    {queue.failed.length}
                  </span>
                </div>
              </div>

              {queue.failed.length > 0 && (
                <div className="rounded-lg border border-red-500/20 bg-red-950/20">
                  <div className="border-b border-red-500/10 px-4 py-2 text-sm font-medium text-red-300">
                    失败任务列表
                  </div>
                  <div className="divide-y divide-white/5">
                    {queue.failed.map((job: FailedJobItem) => (
                      <div key={job.id} className="px-4 py-3">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-3">
                            <code className="font-mono text-xs text-slate-400">
                              #{job.id}
                            </code>
                            <span className="text-xs text-slate-500">
                              {job.queue}
                            </span>
                            <span className="text-xs text-slate-600">
                              {job.failed_at}
                            </span>
                          </div>
                          <div className="flex gap-2">
                            <Button
                              variant="outline"
                              size="sm"
                              className="border-white/10 text-slate-300 hover:bg-white/5"
                              onClick={() =>
                                setExpandedJob(
                                  expandedJob === job.id ? null : job.id,
                                )
                              }
                            >
                              {expandedJob === job.id ? "收起" : "详情"}
                            </Button>
                            <Button
                              variant="outline"
                              size="sm"
                              className="border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10"
                              disabled={retryMutation.isPending}
                              onClick={() => retryMutation.mutate(job.uuid)}
                            >
                              <RefreshCw className="mr-1 h-3 w-3" /> 重试
                            </Button>
                          </div>
                        </div>
                        {expandedJob === job.id && (
                          <pre className="mt-2 max-h-32 overflow-auto rounded bg-black/60 p-2 text-xs text-red-300">
                            {job.exception}
                          </pre>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          ) : null}
        </section>

        {/* 备份列表 */}
        <section>
          <h3 className="mb-3 text-sm font-medium text-slate-300">数据库备份</h3>
          {backups && backups.length > 0 ? (
            <div className="rounded-lg border border-white/10 bg-black/40">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-white/10 text-slate-500">
                    <th className="px-4 py-2 text-left">文件名</th>
                    <th className="px-4 py-2 text-right">大小</th>
                    <th className="px-4 py-2 text-right">时间</th>
                  </tr>
                </thead>
                <tbody>
                  {backups.map((b) => (
                    <tr key={b.name} className="border-b border-white/5">
                      <td className="px-4 py-2 font-mono text-xs text-slate-300">
                        {b.name}
                      </td>
                      <td className="px-4 py-2 text-right font-mono text-slate-400">
                        {formatBytes(b.size)}
                      </td>
                      <td className="px-4 py-2 text-right text-slate-500">
                        {b.mtime}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <p className="text-sm text-slate-500">暂无备份文件</p>
          )}
        </section>

        {/* 最近日志 */}
        <section>
          <h3 className="mb-3 text-sm font-medium text-slate-300">最近日志</h3>
          {logs && logs.length > 0 ? (
            <ScrollArea className="h-64 rounded-lg border border-white/10 bg-black/60">
              <pre className="p-3 text-xs leading-relaxed text-slate-400">
                {logs.join("\n")}
              </pre>
            </ScrollArea>
          ) : (
            <p className="text-sm text-slate-500">暂无日志</p>
          )}
        </section>
      </div>
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/system")({
  component: SystemPage,
});
