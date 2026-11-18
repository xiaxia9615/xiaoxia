import { useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
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
  updateAdminAiProvider,
  type AdminAiProviderItem,
} from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

/** AI 供应商编辑弹窗：api_key 留空表示保留原值。 */
export function EditProviderDialog({
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
        api_key: apiKey || undefined,
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
