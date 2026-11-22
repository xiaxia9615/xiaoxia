import { toast } from "sonner";
import { toErrorMessage } from "@/lib/api";
import type { BuyAddData } from "@/lib/api";
import type { PayChannel, PayStrategy } from "./types";
import { alipayStrategy } from "./strategies/alipay";
import { weixinStrategy } from "./strategies/weixin";
import { TRACK_EVENTS, track } from "@/lib/observability";

/**
 * 支付管理器：集中登记渠道策略并按渠道分发。
 * 新增支付渠道 = 新增 1 个策略文件 + 在 STRATEGIES 登记 1 行。
 */
const STRATEGIES: Record<PayChannel, PayStrategy> = {
  alipay: alipayStrategy,
  weixin: weixinStrategy,
};

/** 供 UI 渲染渠道选项的渠道列表。 */
export const PAY_CHANNELS: ReadonlyArray<{ channel: PayChannel; label: string }> = Object.values(
  STRATEGIES,
).map((s) => ({ channel: s.channel, label: s.label }));

export function isPayChannel(value: string): value is PayChannel {
  return value in STRATEGIES;
}

/** 该渠道当前是否已接通（部分渠道依赖后端补齐 pay_url）。 */
export function getPayStrategy(channel: PayChannel): PayStrategy {
  return STRATEGIES[channel];
}

/**
 * 统一支付入口：按渠道取出策略执行。
 *
 * @returns true 表示支付流程已发起（页面即将跳转）
 */
export async function startPay(
  order: Pick<BuyAddData, "order_ids" | "order_sn">,
  channel: PayChannel = "alipay",
): Promise<boolean> {
  const orderId = order.order_ids[0];
  if (!orderId) {
    throw new Error("订单信息缺失，无法发起支付");
  }
  const strategy = STRATEGIES[channel];
  track(TRACK_EVENTS.payStart, { orderId, channel });

  let launched: boolean;
  try {
    launched = await strategy.pay({ order, orderId });
  } catch (err) {
    track(TRACK_EVENTS.payResult, { orderId, channel, ok: false });
    throw err;
  }
  track(TRACK_EVENTS.payResult, { orderId, channel, ok: launched });

  if (!launched) {
    toast.success("订单已创建，请前往订单页完成支付", {
      description: `订单号 ${order.order_sn}`,
    });
  }
  return launched;
}

/** 支付失败时的统一提示文案。 */
export function payErrorMessage(err: unknown): string {
  return toErrorMessage(err, "发起支付失败，请稍后重试");
}
