import { useCallback, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { deleteAddress, fetchAddresses, setDefaultAddress, type Address } from "@/lib/api";

/**
 * 收货地址簿：列表查询、设为默认、删除，以及编辑/新增弹窗状态。
 * 页面组件只消费本 hook 暴露的状态与动作。
 */
export function useAddressBook() {
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Address | null>(null);

  const listQuery = useQuery({
    queryKey: ["addresses"],
    queryFn: fetchAddresses,
  });

  const openCreate = useCallback(() => {
    setEditing(null);
    setDialogOpen(true);
  }, []);

  const openEdit = useCallback((address: Address) => {
    setEditing(address);
    setDialogOpen(true);
  }, []);

  const defaultMutation = useMutation({
    mutationFn: (id: number) => setDefaultAddress(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["addresses"] });
      toast.success("已设为默认地址");
    },
    onError: () => toast.error("设置默认地址失败", { description: "请稍后重试" }),
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteAddress(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["addresses"] });
      toast.success("地址已删除");
    },
    onError: () => toast.error("删除地址失败", { description: "请稍后重试" }),
  });

  return {
    addresses: listQuery.data ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    error: listQuery.error,
    refetch: listQuery.refetch,
    dialogOpen,
    setDialogOpen,
    editing,
    openCreate,
    openEdit,
    defaultMutation,
    deleteMutation,
  };
}
