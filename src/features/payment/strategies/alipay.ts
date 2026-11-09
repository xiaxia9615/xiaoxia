import { detectPayClient, handleAlipay, paySubmit } from "@/lib/api";
import type { PayChannel, PayContext, PayStrategy } from "../types";

/**
 * 支付宝策略。
 * 分支顺序（与后端 pay/submit 契约对齐）：
 * 1. pay_form（电脑网站支付）：安全重建表单并提交
 * 2. order_str（App/小程序支付串）：my.tradePay / alipays 协议
 * 3. pay_url：跳转收银台
 */
export const alipayStrategy: PayStrategy = {
  channel: "alipay" as PayChannel,
  label: "支付宝",

  async pay({ order, orderId }: PayContext): Promise<boolean> {
    const pay = await paySubmit({
      orderId,
      payment: this.channel,
      client: detectPayClient(),
    });

    if (pay.pay_form || pay.order_str) {
      const launched = handleAlipay(orderId, order.order_sn, {
        order_str: pay.order_str,
        pay_form: pay.pay_form,
      });
      if (launched) return true;
    }
    if (pay.pay_url) {
      window.location.href = pay.pay_url;
      return true;
    }
    return false;
  },
};
