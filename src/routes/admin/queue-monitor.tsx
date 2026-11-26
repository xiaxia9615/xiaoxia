import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { Loader2, RefreshCw, Clock, AlertTriangle, CheckCircle2 } from "lucide-react";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  getQueueStats,
  getSystemHealth,
  retryFailedJob,
  type FailedJobItem,
} from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

function QueueMonitorPage() {
  const queryClient = useQueryClient();
  const [expandedJob, setExpandedJob] = useState<number | null>(null);

  const { data: queue, isLoading } = useQuery({
    queryKey: ["admin-queue-stats"],
    queryFn: () => getQueueStats(),
    refetchInterval: 10000,
  });

  const { data: health } = useQuery({
    queryKey: ["admin-system-health"],
    queryFn: () => getSystemHealth(),
    refetchInterval: 30000,
  });

  const retryMutation = useMutation({
    mutationFn: (uuid: string) => retryFailedJob(uuid),
    onSuccess: () => {
      toast.success("重试已提交", { description: "失败任务已重新入队。" });
      queryClient.invalidateQueries({ queryKey: ["admin-queue-stats"] });
    },
    onError: (err) => {
      toast.error("重试失败", { description: toErrorMessage(err) });
    },
  });

  const workerStatus = health?.worker ?? "-";
  const workerOk = workerStatus === "ok" || workerStatus === "sync";

  return (
    <AdminPageLayout title="队列监控" description="队列任务状态与失败任务管理">
      <div className="space-y-6">
        {/* 概览卡片 */}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div className="rounded-lg border border-white/10 bg-black/40 px-6 py-4">
            <div className="flex items-center gap-2 text-slate-500">
              <Clock className="h-4 w-4" />
              <span className="text-sm">待处理任务</span>
            </div>
            <div className="mt-2 font-mono text-2xl text-slate-200">
              {isLoading ? "…" : queue?.pending ?? 0}
            </div>
          </div>

          <div className="rounded-lg border border-white/10 bg-black/40 px-6 py-4">
            <div className="flex items-center gap-2 text-slate-500">
              <AlertTriangle className="h-4 w-4" />
              <span className="text-sm">失败任务</span>
            </div>
            <div
              className={`mt-2 font-mono text-2xl ${
                (queue?.failed.length ?? 0) > 0 ? "text-red-400" : "text-slate-200"
              }`}
            >
              {isLoading ? "…" : queue?.failed.length ?? 0}
            </div>
          </div>

          <div className="rounded-lg border border-white/10 bg-black/40 px-6 py-4">
            <div className="flex items-center gap-2 text-slate-500">
              <CheckCircle2 className="h-4 w-4" />
              <span className="text-sm">Worker 状态</span>
            </div>
            <div
              className={`mt-2 font-mono text-2xl ${
                workerOk ? "text-emerald-400" : "text-red-400"
              }`}
            >
              {workerStatus}
            </div>
          </div>
        </div>

        {/* 失败任务列表 */}
        <section>
          <div className="mb-3 flex items-center justify-between">
            <h3 className="text-sm font-medium text-slate-300">失败任务列表</h3>
            <Button
              variant="outline"
              size="sm"
              className="border-white/10 text-slate-300 hover:bg-white/5"
              onClick={() =>
                queryClient.invalidateQueries({ queryKey: ["admin-queue-stats"] })
              }
            >
              <RefreshCw className="mr-1 h-3 w-3" /> 刷新
            </Button>
          </div>

          {isLoading ? (
            <div className="flex items-center gap-2 py-8 text-slate-500">
              <Loader2 className="h-4 w-4 animate-spin" /> 加载中…
            </div>
          ) : queue && queue.failed.length > 0 ? (
            <div className="rounded-lg border border-red-500/20 bg-red-950/20">
              <div className="divide-y divide-white/5">
                {queue.failed.map((job: FailedJobItem) => (
                  <div key={job.id} className="px-4 py-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <code className="font-mono text-xs text-slate-400">
                          #{job.id}
                        </code>
                        <span className="rounded bg-white/5 px-2 py-0.5 text-xs text-slate-400">
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
                            setExpandedJob(expandedJob === job.id ? null : job.id)
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
                      <ScrollArea className="mt-2 max-h-40 rounded bg-black/60">
                        <pre className="p-2 text-xs leading-relaxed text-red-300">
                          {job.exception}
                        </pre>
                      </ScrollArea>
                    )}
                  </div>
                ))}
              </div>
            </div>
          ) : (
            <div className="flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-950/10 px-4 py-6 text-sm text-emerald-400">
              <CheckCircle2 className="h-4 w-4" />
              暂无失败任务，队列运行正常
            </div>
          )}
        </section>
      </div>
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/queue-monitor")({
  component: QueueMonitorPage,
});
