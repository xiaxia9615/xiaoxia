import { detectPayClient, handleAlipay, paySubmit } from "@/lib/api";
import type { PayChannel, PayContext, PayResult, PayStrategy } from "../types";

/**
 * 支付宝策略。
 * 分支顺序（与后端 pay/submit 契约对齐）：
 * 1. qr_code（扫码支付）：返回二维码给前端展示弹窗
 * 2. pay_form（电脑网站支付）：安全重建表单并提交
 * 3. order_str（App/小程序支付串）：my.tradePay / alipays 协议
 * 4. pay_url：跳转收银台
 */
export const alipayStrategy: PayStrategy = {
  channel: "alipay" as PayChannel,
  label: "支付宝",
  async pay({ order, orderId }: PayContext): Promise<PayResult> {
    const pay = await paySubmit({
      orderId,
      payment: this.channel,
      client: detectPayClient(),
    });

    // 1. 扫码支付二维码：返回给前端展示弹窗
    if (pay.qr_code) {
      return { type: "qr_code", qrCode: pay.qr_code, orderId };
    }

    // 2. 电脑网站支付表单
    if (pay.pay_form || pay.order_str) {
      const launched = handleAlipay(orderId, order.order_sn, {
        order_str: pay.order_str,
        pay_form: pay.pay_form,
      });
      if (launched) return { type: "launched" };
    }

    // 3. 收银台跳转
    if (pay.pay_url) {
      window.location.href = pay.pay_url;
      return { type: "launched" };
    }

    return { type: "fallback" };
  },
};
