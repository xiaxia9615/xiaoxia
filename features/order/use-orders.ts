import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { toast } from "sonner";
import { toErrorMessage, fetchOrders } from "@/lib/api";
import { startPay } from "@/features/payment";

const STATUS_NAMES: Record<string, string> = {
  "0": "待付款",
  "1": "待发货",
  "2": "待收货",
  "3": "已完成",
  "4": "已取消",
};

function statusText(status?: number): string {
  if (status === undefined) return "处理中";
  return STATUS_NAMES[String(status)] ?? String(status);
}

/**
 * 我的订单：列表查询、单笔发起支付、状态文案。
 * 页面组件只消费本 hook 暴露的状态与动作。
 */
export function useOrders() {
  const [payingId, setPayingId] = useState<number | null>(null);

  const listQuery = useQuery({
    queryKey: ["orders"],
    queryFn: () => fetchOrders(),
  });

  const pay = async (orderId: number) => {
    setPayingId(orderId);
    try {
      await startPay({ order_ids: [orderId], order_sn: "" });
    } catch (err) {
      toast.error("发起支付失败", {
        description: toErrorMessage(err, "请稍后重试"),
      });
    } finally {
      setPayingId(null);
    }
  };

  return {
    orders: listQuery.data?.list ?? [],
    isLoading: listQuery.isLoading,
    isError: listQuery.isError,
    error: listQuery.error,
    refetch: listQuery.refetch,
    payingId,
    pay,
    statusText,
  };
}
