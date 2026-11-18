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
import { forceArchiveProduct, type AdminProductItem } from "@/lib/api/admin";
import { toErrorMessage } from "@/lib/api/request";

/** 强制下架确认弹窗。 */
export function ArchiveProductDialog({
  product,
  open,
  onOpenChange,
}: {
  product: AdminProductItem | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const queryClient = useQueryClient();

  const archiveMutation = useMutation({
    mutationFn: (id: number) => forceArchiveProduct(id),
    onSuccess: () => {
      onOpenChange(false);
      toast.success("已强制下架", { description: `商品「${product?.name}」已下架。` });
      queryClient.invalidateQueries({ queryKey: ["admin-products"] });
    },
    onError: (err) => {
      toast.error("操作失败", { description: toErrorMessage(err) });
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
          <AlertDialogTitle>确认强制下架？</AlertDialogTitle>
          <AlertDialogDescription>
            确认强制下架「{product?.name}」？下架后用户将无法购买，但历史订单不受影响。
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel disabled={archiveMutation.isPending}>取消</AlertDialogCancel>
          <AlertDialogAction
            disabled={archiveMutation.isPending}
            onClick={() => {
              if (product) archiveMutation.mutate(product.id);
            }}
            className="bg-red-600 text-white hover:bg-red-700"
          >
            {archiveMutation.isPending ? "处理中…" : "确认下架"}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
