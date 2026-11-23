import { request } from "./request";

/** 结算预览条目。 */
export interface CheckoutItem {
  goods_id: number;
  name: string;
  image: string;
  spec_text?: string;
  price: number;
  original_price: number;
  count: number;
  stock: number;
  /** 商品性质：physical 实物 / virtual 虚拟（后端补齐后返回，缺省按实物处理）。 */
  nature?: "physical" | "virtual";
}

export interface CheckoutPayment {
  id: number;
  name: string;
}

/** 结算预览返回（buy/index）。 */
export interface CheckoutData {
  items: CheckoutItem[];
  total_price: number;
  shipping_amount: number;
  actual_price: number;
  goods_count: number;
  address: {
    id: number;
    name: string;
    phone: string;
    address: string;
    is_default: boolean;
  } | null;
  payments: CheckoutPayment[];
  default_payment_id: number;
}

/** 结算预览：需登录，参数 buy_type + ids。 */
export async function fetchCheckout(input: {
  buyType: "cart";
  ids: number[];
}): Promise<CheckoutData> {
  const data = await request<Omit<CheckoutData, 'items'> & {
    items?: Array<CheckoutItem & { goods_name?: string; quantity?: number }>;
  }>("/buy/index", {
    method: "POST",
    auth: true,
    body: {
      buy_type: input.buyType,
      ids: input.ids.join(","),
    },
  });
  // 后端返回 goods_name/quantity，前端期望 name/count，做字段映射
  return {
    ...data,
    items: (data.items ?? []).map((it) => ({
      goods_id: Number(it.goods_id ?? 0),
      name: String(it.name ?? it.goods_name ?? ""),
      image: String(it.image ?? ""),
      spec_text: typeof it.spec_text === "string" ? it.spec_text : undefined,
      price: Number(it.price ?? 0),
      original_price: Number(it.original_price ?? it.price ?? 0),
      count: Number(it.count ?? it.quantity ?? 1),
      stock: Number(it.stock ?? 0),
      nature: it.nature === "virtual" ? "virtual" : "physical",
    })),
  };
}

/** 下单返回（buy/add）。 */
export interface BuyAddData {
  order_ids: number[];
  payment_id: number;
  order_sn: string;
}

/** 创建订单：需登录。支持购物车下单（cart）与直接购买（goods）。 */
export async function buyAdd(input:
  | { buyType: "cart"; ids: number[]; addressId: number; paymentId?: number }
  | { buyType: "goods"; goodsData: Array<{ goods_id: number; stock?: number }>; addressId: number; paymentId?: number },
): Promise<BuyAddData> {
  const body: Record<string, unknown> = {
    buy_type: input.buyType,
    address_id: input.addressId,
    payment_id: input.paymentId ?? 0,
  };
  if (input.buyType === "cart") {
    body.ids = input.ids.join(",");
  } else {
    body.goods_data = input.goodsData;
  }
  return request<BuyAddData>("/buy/add", {
    method: "POST",
    auth: true,
    body,
  });
}

/** 发起支付返回（pay/submit）。 */
export interface PaySubmitData {
  pay_log_id: number;
  order_id: number;
  order_sn: string;
  amount: number;
  payment: string;
  payment_name: string;
  pay_status: number;
  /** 支付宝 App/小程序支付串（alipay.trade.app.pay），后端补齐后返回。 */
  order_str?: string;
  /** 收银台跳转 URL（电脑网站支付），后端补齐后返回。 */
  pay_url?: string;
  /** 电脑网站支付 HTML 表单（alipay.trade.page.pay），后端生成，前端渲染提交。 */
  pay_form?: string;
  /** 客户端类型：app / mini / pc。 */
  client?: string;
}

/** 发起支付：需登录。payment 为 alipay / weixin，client 为 app / mini / pc。 */
export async function paySubmit(input: {
  orderId: number;
  payment: string;
  client?: "app" | "mini" | "pc";
}): Promise<PaySubmitData> {
  return request<PaySubmitData>("/pay/submit", {
    method: "POST",
    auth: true,
    body: {
      order_id: input.orderId,
      payment: input.payment,
      client: input.client ?? "app",
    },
  });
}

/** 查询订单支付状态：需登录。 */
export async function payQuery(orderId: number): Promise<{
  order_id: number;
  order_sn: string;
  order_status: number;
  status_name: string;
  pay_status: number;
  pay_time: number;
  amount: number;
}> {
  return request("/pay/query", {
    auth: true,
    params: { order_id: orderId },
  });
}

/** 根据运行环境判断支付宝客户端类型：小程序/App 容器用 app，其余（含 PC）用 pc。 */
export function detectPayClient(): "app" | "pc" {
  const win = window as Window & { my?: { tradePay?: unknown } };
  if (win.my?.tradePay) return "app";
  return "pc";
}

/**
 * 提交支付宝支付：优先使用后端返回的 PC 端 HTML 表单；
 * 否则使用 App/小程序支付串（order_str）走 SDK 或 alipays 唤起。
 */
export function handleAlipay(
  orderId: number,
  orderSn: string,
  payData: { order_str?: string; pay_form?: string },
): boolean {
  const win = window as Window & {
    my?: { tradePay?: (opts: { tradeNO: string; onComplete?: () => void }) => void };
  };

  // 1. PC 端：用 DOMParser 安全解析后端返回的支付表单（不执行脚本、
  //    不触发内联事件），仅重建 form + input 后提交
  if (payData.pay_form) {
    try {
      const parsed = new DOMParser().parseFromString(payData.pay_form, "text/html");
      const form = parsed.querySelector("form");
      if (form) {
        const clean = document.createElement("form");
        clean.action = form.getAttribute("action") ?? "";
        clean.method = form.getAttribute("method") ?? "post";
        parsed.querySelectorAll("form input").forEach((input) => {
          const cloned = document.createElement("input");
          cloned.type = input.getAttribute("type") ?? "hidden";
          cloned.name = input.getAttribute("name") ?? "";
          cloned.value = input.getAttribute("value") ?? "";
          clean.appendChild(cloned);
        });
        if (clean.action) {
          document.body.appendChild(clean);
          clean.submit();
          return true;
        }
      }
    } catch {
      // 渲染失败，回退到 alipays 唤起
    }
  }

  const orderStr = payData.order_str;
  if (!orderStr) return false;

  // 2. 支付宝小程序/App 环境：用 SDK tradePay
  if (win.my?.tradePay) {
    win.my.tradePay({
      tradeNO: orderStr,
      onComplete: () => {
        // 项目使用 hash 路由，跳转到 #/orders
        window.location.hash = "/orders";
      },
    });
    return true;
  }

  // 3. 移动端浏览器：alipays 协议唤起支付宝 App
  try {
    const alipayUrl =
      `alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718` +
      `&qrcode=${encodeURIComponent(orderStr)}`;
    window.location.href = alipayUrl;
    return true;
  } catch {
    return false;
  }
}
