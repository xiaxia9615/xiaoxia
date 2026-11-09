import { fmtTime, num, request } from "./request";

/** 后端订单列表 DTO（显式契约声明）。 */
export interface OrderDto {
  id?: number;
  order_sn?: string;
  items?: Array<{ name?: string; image?: string; quantity?: number }>;
  goods_name?: string;
  goods_image?: string;
  quantity?: number;
  price?: number;
  total_price?: number;
  total_amount?: number;
  pay_amount?: number;
  order_status?: number;
  status_name?: string;
  add_time?: number;
}

/** 订单列表条目（页面模型）。 */
export interface OrderItem {
  id: number;
  order_sn?: string;
  goods_name?: string;
  goods_image?: string;
  price?: number;
  quantity?: number;
  total_price?: number;
  total_amount?: number;
  pay_amount?: number;
  order_status?: number;
  status_name?: string;
  add_time?: number;
  created_at: string;
}

/** DTO -> 订单列表条目：取首个商品信息，add_time 格式化为 created_at。 */
export function toOrderItem(dto: OrderDto): OrderItem {
  const first = dto.items?.[0];
  return {
    id: num(dto.id),
    order_sn: dto.order_sn,
    goods_name: first?.name ?? dto.goods_name,
    goods_image: first?.image ?? dto.goods_image,
    quantity: first?.quantity ?? dto.quantity,
    price: dto.price,
    total_price: dto.total_price,
    total_amount: dto.total_amount,
    pay_amount: dto.pay_amount,
    order_status: dto.order_status,
    status_name: dto.status_name,
    add_time: dto.add_time,
    created_at: fmtTime(dto.add_time),
  };
}

/** 订单列表（需登录）。错误上抛由 React Query 呈现，登录态过期走全局登出。 */
export async function fetchOrders(
  query: { page?: number; limit?: number } = {},
): Promise<{ list: OrderItem[]; total: number }> {
  const data = await request<{ list?: OrderDto[]; data?: OrderDto[]; total?: number } | OrderDto[]>(
    "/order/index",
    { auth: true, params: { page: query.page ?? 1, page_size: query.limit ?? 10 } },
  );
  const rawList = Array.isArray(data) ? data : (data.data ?? data.list ?? []);
  const total = !Array.isArray(data) && typeof data.total === "number"
    ? data.total
    : rawList.length;
  return { list: rawList.map(toOrderItem), total };
}

export async function cancelOrder(orderId: number): Promise<boolean> {
  try {
    await request<Record<string, unknown>>("/order/cancel", {
      auth: true,
      method: "POST",
      body: { order_id: orderId },
    });
    return true;
  } catch {
    return false;
  }
}

/** 订单详情 DTO。 */
export interface OrderDetailDto {
  id?: number;
  order_sn?: string;
  order_status?: number;
  status_name?: string;
  pay_status?: number;
  goods_amount?: number;
  shipping_fee?: number;
  total_amount?: number;
  pay_amount?: number;
  receiver_name?: string;
  receiver_mobile?: string;
  receiver_address?: string;
  buyer_remark?: string;
  items?: Array<{
    id?: number;
    goods_id?: number;
    name?: string;
    goods_name?: string;
    image?: string;
    price?: number;
    quantity?: number;
  }>;
  address?: { name?: string; tel?: string; address?: string };
  add_time?: string;
  pay_time?: string;
  order_data?: Record<string, unknown>;
  data?: Record<string, unknown>;
}

/** 订单详情（页面模型）。 */
export interface OrderDetailData {
  id: number;
  order_sn: string;
  order_status: number;
  status_name: string;
  pay_status: number;
  goods_amount: number;
  shipping_fee: number;
  total_amount: number;
  pay_amount: number;
  receiver_name: string;
  receiver_mobile: string;
  receiver_address: string;
  buyer_remark: string;
  items: Array<{ id: number; goods_id: number; name: string; image: string; price: number; quantity: number }>;
  add_time?: string;
  pay_time?: string;
}

/** DTO -> 订单详情：兼容 order_data / data 嵌套与 address 结构。 */
export function toOrderDetail(dto: OrderDetailDto): OrderDetailData {
  const items = (dto.items ?? []).map((it) => ({
    id: num(it.id),
    goods_id: num(it.goods_id),
    name: it.name ?? it.goods_name ?? "",
    image: it.image ?? "",
    price: num(it.price),
    quantity: num(it.quantity),
  }));
  return {
    id: num(dto.id),
    order_sn: dto.order_sn ?? "",
    order_status: num(dto.order_status),
    status_name: dto.status_name ?? "",
    pay_status: num(dto.pay_status),
    goods_amount: num(dto.goods_amount),
    shipping_fee: num(dto.shipping_fee),
    total_amount: num(dto.total_amount),
    pay_amount: num(dto.pay_amount),
    receiver_name: dto.receiver_name ?? dto.address?.name ?? "",
    receiver_mobile: dto.receiver_mobile ?? dto.address?.tel ?? "",
    receiver_address: dto.receiver_address ?? dto.address?.address ?? "",
    buyer_remark: dto.buyer_remark ?? "",
    items,
    add_time: dto.add_time,
    pay_time: dto.pay_time,
  };
}

export async function fetchOrderDetail(orderId: number): Promise<OrderDetailData | null> {
  try {
    const res = await request<OrderDetailDto>("/order/detail", {
      auth: true,
      params: { id: orderId },
    });
    // 后端可能把订单体包在 order_data / data 里
    const nested = res.order_data ?? res.data;
    const dto = (nested ?? res) as OrderDetailDto;
    return toOrderDetail(dto);
  } catch {
    return null;
  }
}

export async function deleteOrder(orderId: number): Promise<boolean> {
  try {
    await request<Record<string, unknown>>("/order/delete", {
      auth: true,
      method: "POST",
      body: { order_id: orderId },
    });
    return true;
  } catch {
    return false;
  }
}
