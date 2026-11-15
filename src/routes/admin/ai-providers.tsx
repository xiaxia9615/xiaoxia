import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  getAdminAiProviders,
  updateAdminAiProvider,
  type AdminAiProviderItem,
} from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

/** AI 供应商编辑弹窗：api_key 留空表示保留原值。 */
function EditProviderDialog({
  provider,
  open,
  onOpenChange,
}: {
  provider: AdminAiProviderItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const queryClient = useQueryClient();
  const [baseUrl, setBaseUrl] = useState("");
  const [apiKey, setApiKey] = useState("");
  const [enabled, setEnabled] = useState(true);
  const [initialized, setInitialized] = useState(false);

  // 弹窗打开时用当前行数据初始化表单
  if (open && provider && !initialized) {
    setBaseUrl(provider.base_url ?? "");
    setApiKey("");
    setEnabled(provider.enabled);
    setInitialized(true);
  }
  if (!open && initialized) {
    setInitialized(false);
  }

  const updateMutation = useMutation({
    mutationFn: () =>
      updateAdminAiProvider(provider!.id, {
        base_url: baseUrl,
        api_key: apiKey,
        enabled,
      }),
    onSuccess: (result) => {
      toast.success("供应商已更新", {
        description: `当前 Key：${result.masked_key}`,
      });
      onOpenChange(false);
      queryClient.invalidateQueries({ queryKey: ["admin-ai-providers"] });
    },
    onError: (err) => {
      toast.error("更新失败", { description: toErrorMessage(err) });
    },
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>编辑供应商：{provider?.display_name ?? provider?.name}</DialogTitle>
          <DialogDescription>
            当前 Key：{provider?.masked_key}。API Key 留空表示保留原值不变。
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-2">
          <div className="space-y-1.5">
            <Label htmlFor="provider-base-url">Endpoint（base_url）</Label>
            <Input
              id="provider-base-url"
              value={baseUrl}
              onChange={(e) => setBaseUrl(e.target.value)}
              placeholder="https://api.example.com/v1"
            />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="provider-api-key">API Key（留空保留原值）</Label>
            <Input
              id="provider-api-key"
              type="password"
              value={apiKey}
              onChange={(e) => setApiKey(e.target.value)}
              placeholder="留空表示不修改"
              autoComplete="new-password"
            />
          </div>
          <div className="flex items-center justify-between">
            <Label htmlFor="provider-enabled">启用状态</Label>
            <Switch
              id="provider-enabled"
              checked={enabled}
              onCheckedChange={setEnabled}
            />
          </div>
        </div>
        <DialogFooter>
          <Button
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={updateMutation.isPending}
          >
            取消
          </Button>
          <Button
            onClick={() => updateMutation.mutate()}
            disabled={updateMutation.isPending}
          >
            {updateMutation.isPending ? "提交中…" : "保存"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function AiProvidersPage() {
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState<AdminAiProviderItem | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ["admin-ai-providers", page],
    queryFn: () => getAdminAiProviders({ page }),
  });

  const columns: Array<DataTableColumn<AdminAiProviderItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    {
      key: "name",
      header: "供应商",
      cell: (row) => row.display_name ?? row.name,
    },
    { key: "driver", header: "驱动", cell: (row) => row.driver },
    { key: "model_name", header: "默认模型", cell: (row) => row.model_name },
    {
      key: "base_url",
      header: "Endpoint",
      cell: (row) => (
        <span className="font-mono text-xs text-gray-500">{row.base_url ?? "-"}</span>
      ),
    },
    {
      key: "masked_key",
      header: "API Key",
      cell: (row) => (
        <code className="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-600">
          {row.masked_key}
        </code>
      ),
    },
    { key: "models_count", header: "模型数", cell: (row) => row.models_count },
    {
      key: "enabled",
      header: "状态",
      cell: (row) =>
        row.enabled ? (
          <StatusBadge status="启用" type="success" />
        ) : (
          <StatusBadge status="停用" type="danger" />
        ),
    },
    {
      key: "actions",
      header: "操作",
      cell: (row) => (
        <Button variant="outline" size="sm" onClick={() => setEditing(row)}>
          编辑
        </Button>
      ),
    },
  ];

  return (
    <AdminPageLayout title="AI 供应商" description="供应商接入配置与密钥管理">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无供应商"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />
      <EditProviderDialog
        provider={editing}
        open={editing !== null}
        onOpenChange={(open) => {
          if (!open) setEditing(null);
        }}
      />
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/ai-providers")({
  component: AiProvidersPage,
});
