import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { cancelAftersale, fetchAftersales } from "@/lib/api";

/** 售后申请列表：查询与撤销。 */
export function useAftersales() {
  const queryClient = useQueryClient();

  const listQuery = useQuery({
    queryKey: ["aftersales"],
    queryFn: fetchAftersales,
  });

  const cancelMutation = useMutation({
    mutationFn: cancelAftersale,
    onSuccess: (ok) => {
      if (!ok) {
        toast.error("撤销失败", { description: "请稍后重试" });
        return;
      }
      toast.success("已撤销售后申请");
      void queryClient.invalidateQueries({ queryKey: ["aftersales"] });
    },
  });

  return {
    aftersales: listQuery.data ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    error: listQuery.error,
    refetch: listQuery.refetch,
    cancelMutation,
  };
}
