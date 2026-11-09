import { emit } from "./transport";

/**
 * 轻量埋点。
 *
 * 用法：track("add_to_cart", { goodsId, quantity })
 *
 * 约束：
 * - 只记录行为与业务标识，不记录手机号、地址、密码等个人信息；
 * - 事件名用 snake_case，属性用 camelCase；
 * - 未配置上报通道时不会产生网络请求（见 transport.ts）。
 */

/** 关键路径事件名集中声明，避免各处拼写不一致 */
export const TRACK_EVENTS = {
  pageView: "page_view",
  productView: "product_view",
  addToCart: "add_to_cart",
  beginCheckout: "begin_checkout",
  placeOrder: "place_order",
  payStart: "pay_start",
  payResult: "pay_result",
  login: "login",
  register: "register",
  search: "search",
  favoriteToggle: "favorite_toggle",
  contributeSubmit: "contribute_submit",
} as const;

export type TrackEvent = (typeof TRACK_EVENTS)[keyof typeof TRACK_EVENTS];

/** 记录一次业务事件 */
export function track(event: TrackEvent | string, props?: Record<string, unknown>): void {
  emit({ kind: "event", name: event, props });
}

/** 记录一次页面访问（由根路由的路由变化触发） */
export function trackPageView(path: string): void {
  emit({ kind: "event", name: TRACK_EVENTS.pageView, props: { path } });
}
