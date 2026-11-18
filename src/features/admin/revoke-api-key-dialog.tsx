import { useMutation, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { revokeAdminApiKey, type AdminApiKeyItem } from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

/** API Key 吊销确认弹窗。 */
export function RevokeApiKeyDialog({
  item,
  open,
  onOpenChange,
}: {
  item: AdminApiKeyItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const queryClient = useQueryClient();

  const revokeMutation = useMutation({
    mutationFn: (row: AdminApiKeyItem) => revokeAdminApiKey(row.id),
    onSuccess: () => {
      onOpenChange(false);
      toast.success("Key 已吊销", { description: "该 Key 立即失效。" });
      queryClient.invalidateQueries({ queryKey: ["admin-api-keys"] });
    },
    onError: (err) => {
      toast.error("吊销失败", { description: toErrorMessage(err) });
    },
  });

  return (
    <AlertDialog
      open={open}
      onOpenChange={(o) => {
        if (!o) onOpenChange(false);
      }}
    >
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>确认吊销此 Key？</AlertDialogTitle>
          <AlertDialogDescription>
            确认吊销 {item?.name}（{item?.masked_key}）？吊销后立即失效，不可恢复。
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={revokeMutation.isPending}>取消</AlertDialogCancel>
          <AlertDialogAction
            disabled={revokeMutation.isPending}
            onClick={() => {
              if (item) revokeMutation.mutate(item);
            }}
            className="bg-red-600 text-white hover:bg-red-700"
          >
            {revokeMutation.isPending ? "吊销中…" : "确认吊销"}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
