import { paySubmit } from "@/lib/api";
import type { PayContext, PayStrategy } from "../types";

/**
 * PayPal 策略：走后端返回的收银台跳转地址（pay_url）。
 * 若后端后续改为返回 approve link 等其他形态，只需改本文件。
 */
export const paypalStrategy: PayStrategy = {
  channel: "paypal",
  label: "PayPal",

  async pay({ orderId }: PayContext): Promise<boolean> {
    const pay = await paySubmit({
      orderId,
      payment: this.channel,
      client: "pc",
    });
    if (pay.pay_url) {
      window.location.href = pay.pay_url;
      return true;
    }
    return false;
  },
};
