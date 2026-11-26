import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  getAdminPendingSkills,
  auditSkill,
  type PendingSkillItem,
} from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

function skillStatusLabel(status: string): string {
  const map: Record<string, string> = {
    draft: "草稿",
    pending: "待审核",
    published: "已通过",
    rejected: "已驳回",
    archived: "已归档",
  };
  return map[status] ?? status;
}

function skillStatusType(status: string): "success" | "warning" | "danger" | "default" {
  if (status === "published") return "success";
  if (status === "pending") return "warning";
  if (status === "rejected") return "danger";
  return "default";
}

function SkillReviewsPage() {
  const [page, setPage] = useState(1);
  const [auditTarget, setAuditTarget] = useState<PendingSkillItem | null>(null);
  const [auditAction, setAuditAction] = useState<"approve" | "reject">("approve");
  const [auditNote, setAuditNote] = useState("");
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["admin-pending-skills", page],
    queryFn: () => getAdminPendingSkills({ page }),
  });

  const auditMutation = useMutation({
    mutationFn: (input: { id: number; approve: boolean; note?: string }) =>
      auditSkill(input.id, input.approve, input.note),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin-pending-skills"] });
      toast.success(auditAction === "approve" ? "审核通过" : "已驳回", {
        description: `技能 #${auditTarget?.id} 已处理。`,
      });
      setAuditTarget(null);
      setAuditNote("");
    },
    onError: (err) => {
      toast.error("审核操作失败", { description: toErrorMessage(err) });
    },
  });

  const submitAudit = () => {
    if (!auditTarget) return;
    if (auditAction === "reject" && !auditNote.trim()) {
      toast.error("驳回时必须填写原因");
      return;
    }
    auditMutation.mutate({
      id: auditTarget.id,
      approve: auditAction === "approve",
      note: auditNote.trim() || undefined,
    });
  };

  const openAudit = (row: PendingSkillItem, action: "approve" | "reject") => {
    setAuditTarget(row);
    setAuditAction(action);
    setAuditNote("");
  };

  const columns: Array<DataTableColumn<PendingSkillItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    {
      key: "product_name",
      header: "技能名称",
      cell: (row) => row.product_name ?? "-",
    },
    {
      key: "endpoint",
      header: "Endpoint",
      cell: (row) => (
        <span className="font-mono text-xs text-gray-500">{row.endpoint ?? "-"}</span>
      ),
    },
    { key: "method", header: "方法", cell: (row) => row.method ?? "-" },
    {
      key: "pricing_type",
      header: "计费",
      cell: (row) => (row.pricing_type === 1 ? "按次" : "按量"),
    },
    {
      key: "status",
      header: "状态",
      cell: (row) => (
        <StatusBadge status={skillStatusLabel(row.status)} type={skillStatusType(row.status)} />
      ),
    },
    {
      key: "created_at",
      header: "提交时间",
      cell: (row) => row.created_at ?? "-",
    },
    {
      key: "actions",
      header: "操作",
      cell: (row) => (
        <div className="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            className="border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10"
            disabled={auditMutation.isPending}
            onClick={() => openAudit(row, "approve")}
          >
            通过
          </Button>
          <Button
            variant="outline"
            size="sm"
            className="border-red-500/30 text-red-400 hover:bg-red-500/10"
            disabled={auditMutation.isPending}
            onClick={() => openAudit(row, "reject")}
          >
            驳回
          </Button>
        </div>
      ),
    },
  ];

  return (
    <AdminPageLayout title="技能审核" description="审核开发者提交的技能上架申请">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无待审核技能"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />

      <Dialog
        open={auditTarget !== null}
        onOpenChange={(o) => {
          if (!o && !auditMutation.isPending) setAuditTarget(null);
        }}
      >
        <DialogContent className="border-white/10 bg-zinc-950/90 backdrop-blur-xl sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="text-slate-100">
              {auditAction === "approve" ? "审核通过" : "驳回技能"}
            </DialogTitle>
            <DialogDescription className="text-slate-500">
              技能 #{auditTarget?.id} — {auditTarget?.product_name}
              {auditAction === "approve"
                ? "，确认通过审核后技能将上架。"
                : "，驳回时必须填写原因。"}
            </DialogDescription>
          </DialogHeader>
          {auditAction === "reject" && (
            <div className="space-y-2">
              <Label className="text-slate-300">驳回原因</Label>
              <Textarea
                value={auditNote}
                onChange={(e) => setAuditNote(e.target.value)}
                placeholder="请填写驳回原因（必填，不超过 255 字）"
                maxLength={255}
                className="border-white/10 bg-black/40 text-slate-100 placeholder:text-slate-600 focus-visible:border-emerald-400/50 focus-visible:ring-emerald-400/30"
              />
            </div>
          )}
          {auditAction === "approve" && (
            <div className="space-y-2">
              <Label className="text-slate-300">备注（可选）</Label>
              <Textarea
                value={auditNote}
                onChange={(e) => setAuditNote(e.target.value)}
                placeholder="审核备注（可选）"
                maxLength={255}
                className="border-white/10 bg-black/40 text-slate-100 placeholder:text-slate-600 focus-visible:border-emerald-400/50 focus-visible:ring-emerald-400/30"
              />
            </div>
          )}
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              className="border-white/10 bg-transparent text-slate-300 hover:bg-white/5"
              disabled={auditMutation.isPending}
              onClick={() => setAuditTarget(null)}
            >
              取消
            </Button>
            <Button
              type="button"
              disabled={auditMutation.isPending}
              className={
                auditAction === "approve"
                  ? "bg-emerald-600 text-white hover:bg-emerald-500"
                  : "bg-red-600 text-white hover:bg-red-500"
              }
              onClick={submitAudit}
            >
              {auditMutation.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  处理中…
                </>
              ) : auditAction === "approve" ? (
                "确认通过"
              ) : (
                "确认驳回"
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/skill-reviews")({
  component: SkillReviewsPage,
});
