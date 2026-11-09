import type { CheckStep } from "./types";

/** 自检步骤定义（顺序即执行顺序） */
export const INITIAL_STEPS: CheckStep[] = [
  { key: "login", title: "登录态校验", detail: "校验本地令牌与用户会话", status: "idle" },
  { key: "client", title: "支付客户端识别", detail: "识别当前浏览器/小程序支付环境", status: "idle" },
  { key: "pay", title: "支付接口连通 (pay/submit)", detail: "对首个待付款订单发起支付探测", status: "idle" },
  { key: "query", title: "支付状态回查 (pay/query)", detail: "回查订单支付状态，验证链路闭环", status: "idle" },
];

/** 终端日志时间戳 */
export function nowStamp(): string {
  const d = new Date();
  const pad = (n: number, w = 2) => String(n).padStart(w, "0");
  return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}.${pad(d.getMilliseconds(), 3)}`;
}

export function isMobileNow(): boolean {
  if (typeof navigator === "undefined") return false;
  return /Android|iPhone|iPad|Mobile/i.test(navigator.userAgent);
}
