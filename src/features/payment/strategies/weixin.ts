import { detectPayClient, paySubmit } from "@/lib/api";
import type { PayContext, PayResult, PayStrategy } from "../types";

/**
 * 微信支付策略。
 * 后端在 pay/submit 中返回 H5/收银台跳转地址（pay_url）；
 * 小程序环境后续可在此接入 wx.requestPayment，仅需改动本文件。
 */
export const weixinStrategy: PayStrategy = {
  channel: "weixin",
  label: "微信支付",

  async pay({ orderId }: PayContext): Promise<PayResult> {
    const pay = await paySubmit({
      orderId,
      payment: this.channel,
      client: detectPayClient(),
    });
    if (pay.pay_url) {
      window.location.href = pay.pay_url;
      return { type: "launched" };
    }
    return { type: "fallback" };
  },
};
