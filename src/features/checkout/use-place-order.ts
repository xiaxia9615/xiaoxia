import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";
import { toast } from "sonner";
import { buyAdd, fetchAddresses, fetchCheckout, toErrorMessage, type CheckoutData } from "@/lib/api";
import { startPay, type PayChannel } from "@/features/payment";
import { TRACK_EVENTS, track } from "@/lib/observability";

/**
 * 结算页业务逻辑（原页面内的下单流程）：
 * 结算预览 + 地址选择 + 选支付渠道 + 下单并唤起支付。
 * 页面组件只负责渲染，不再持有交易流程。
 */
export function usePlaceOrder(ids: string) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [payment, setPayment] = useState<PayChannel>("alipay");
  const [qrDialog, setQrDialog] = useState<{ open: boolean; qrCode: string; orderId: number }>({
    open: false,
    qrCode: "",
    orderId: 0,
  });

  const idArr = ids
    .split(",")
    .map((v) => Number(v))
    .filter((v) => Number.isFinite(v) && v > 0);

  const addressesQuery = useQuery({
    queryKey: ["addresses"],
    queryFn: fetchAddresses,
    enabled: idArr.length > 0,
  });

  const checkoutQuery = useQuery({
    queryKey: ["checkout", ids],
    queryFn: () => fetchCheckout({ buyType: "cart", ids: idArr }),
    enabled: idArr.length > 0,
  });

  const placeOrder = useMutation({
    mutationFn: async () => {
      const addresses = addressesQuery.data ?? [];
      const defaultAddr = addresses.find((a) => a.is_default) ?? addresses[0];
      if (!defaultAddr) {
        throw new Error("请先添加收货地址");
      }
      const payments: CheckoutData["payments"] = checkoutQuery.data?.payments ?? [];
      const selected = payments.find((p) =>
        p.name.toLowerCase().includes(payment === "alipay" ? "alipay" : payment),
      );
      // 从结算预览中提取商品 ID（goods_id），而非购物车项 ID
      const productIds = (checkoutQuery.data?.items ?? []).map((it) => it.goods_id);
      if (productIds.length === 0) {
        throw new Error("没有可结算的商品");
      }
      const order = await buyAdd({
        buyType: "cart",
        ids: productIds,
        addressId: defaultAddr.address_id,
        paymentId: selected?.id,
      });
      if (!order.order_ids?.length) throw new Error("下单失败，请稍后重试");
      track(TRACK_EVENTS.placeOrder, { orderId: order.order_ids[0], channel: payment });
      const result = await startPay(order, payment);
      return { order, result };
    },
    onSuccess: async ({ result }) => {
      if (result.type === "qr_code") {
        setQrDialog({
          open: true,
          qrCode: result.qrCode,
          orderId: result.orderId,
        });
        toast.success("订单已创建，请扫码支付");
        return;
      }
      toast.success("下单成功");
      await queryClient.invalidateQueries({ queryKey: ["cart"] });
      await navigate({ to: "/orders" });
    },
    onError: (err) => {
      const message = toErrorMessage(err, "下单失败，请重试");
      toast.error(message);
      if (message.includes("收货地址")) {
        void navigate({ to: "/address" });
      }
    },
  });

  return {
    idArr,
    payment,
    setPayment,
    placeOrder,
    qrDialog,
    closeQrDialog: () => setQrDialog((prev) => ({ ...prev, open: false })),
    onQrPaySuccess: async () => {
      setQrDialog((prev) => ({ ...prev, open: false }));
      await queryClient.invalidateQueries({ queryKey: ["cart"] });
      await navigate({ to: "/orders" });
    },
    /** 提交订单（同一动作出口，供页面按钮调用，防重复提交） */
    submitOrder: () => {
      if (placeOrder.isPending) return;
      placeOrder.mutate();
    },
    checkout: checkoutQuery.data,
    checkoutError: checkoutQuery.error,
    isLoading: checkoutQuery.isLoading,
    addresses: addressesQuery.data ?? [],
  };
}
