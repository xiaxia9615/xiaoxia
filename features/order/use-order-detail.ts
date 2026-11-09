import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";
import { toast } from "sonner";
import { applyAftersale, cancelOrder, deleteOrder, fetchOrderDetail, toErrorMessage } from "@/lib/api";
import { startPay, payErrorMessage } from "@/features/payment";

/** 售后申请弹窗的表单状态。 */
export interface AftersaleTarget {
  orderDetailId: number;
  goodsName: string;
}

/**
 * 订单详情页业务逻辑：详情查询 + 取消/删除/支付/申请售后。
 * 页面组件只负责渲染。
 */
export function useOrderDetail(orderId: string) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const id = Number(orderId);
  const [asTarget, setAsTarget] = useState<AftersaleTarget | null>(null);
  const [asReason, setAsReason] = useState("");

  const detailQuery = useQuery({
    queryKey: ["order-detail", orderId],
    queryFn: () => fetchOrderDetail(id),
  });

  const cancelMut = useMutation({
    mutationFn: () => cancelOrder(id),
    onSuccess: (ok) => {
      if (!ok) {
        toast.error("取消失败", { description: "请稍后重试" });
        return;
      }
      toast.success("订单已取消");
      void queryClient.invalidateQueries({ queryKey: ["orders"] });
      void detailQuery.refetch();
    },
  });

  const deleteMut = useMutation({
    mutationFn: () => deleteOrder(id),
    onSuccess: (ok) => {
      if (!ok) {
        toast.error("删除失败", { description: "请稍后重试" });
        return;
      }
      toast.success("订单已删除");
      void navigate({ to: "/orders" });
    },
  });

  const payMut = useMutation({
    mutationFn: () => startPay({ order_ids: [id], order_sn: detailQuery.data?.order_sn ?? "" }),
    onError: (err) => {
      toast.error("发起支付失败", { description: payErrorMessage(err) });
    },
    onSuccess: (launched) => {
      if (!launched) toast.info("支付待完成，可在订单页稍后重试");
    },
  });

  const aftersaleMut = useMutation({
    mutationFn: async () => {
      if (!asTarget) throw new Error("请选择需要申请售后的商品");
      return applyAftersale({
        type: "refund",
        order_detail_id: asTarget.orderDetailId,
        reason: asReason || "其他",
      });
    },
    onSuccess: (ok) => {
      if (!ok) {
        toast.error("申请提交失败", { description: "请稍后重试" });
        return;
      }
      toast.success("售后申请已提交");
      setAsTarget(null);
      setAsReason("");
    },
    onError: (err) => {
      toast.error(toErrorMessage(err, "售后申请提交失败"));
    },
  });

  return {
    order: detailQuery.data,
    isLoading: detailQuery.isLoading,
    isError: detailQuery.isError,
    error: detailQuery.error,
    refetch: detailQuery.refetch,
    cancelMut,
    deleteMut,
    payMut,
    aftersaleMut,
    asTarget,
    setAsTarget,
    asReason,
    setAsReason,
  };
}
