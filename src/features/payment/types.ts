import type { BuyAddData } from "@/lib/api";

/** 支持的支付渠道。新增渠道时先在此登记类型。 */
export type PayChannel = "alipay" | "weixin" | "paypal";

/** 支付上下文：由 manager 组装，策略只消费。 */
export interface PayContext {
  order: Pick<BuyAddData, "order_ids" | "order_sn">;
  /** 主订单 id */
  orderId: number;
}

/**
 * 支付策略：一个渠道一份实现。
 * 返回 true 表示已成功唤起/跳转（页面即将离开）。
 */
export interface PayStrategy {
  readonly channel: PayChannel;
  /** 展示名，用于 UI 与提示文案 */
  readonly label: string;
  /** 调用后端 pay/submit 并按环境完成唤起 */
  pay(ctx: PayContext): Promise<boolean>;
}
