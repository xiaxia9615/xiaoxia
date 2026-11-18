import { degrade, num, request } from "./request";

/** 站内消息 DTO。 */
export interface MessageDto {
  id?: number;
  title?: string;
  content?: string;
  type?: number;
  is_read?: number;
  add_time?: string;
}

export interface MessageItem {
  id: number;
  title?: string;
  content?: string;
  type?: number;
  is_read?: number;
  add_time?: string;
}

export function toMessage(dto: MessageDto): MessageItem {
  return {
    id: num(dto.id),
    title: dto.title,
    content: dto.content,
    type: dto.type,
    is_read: dto.is_read,
    add_time: dto.add_time,
  };
}

export async function fetchMessages(): Promise<MessageItem[]> {
  return degrade("消息列表", [], async () => {
    const data = await request<{ data?: MessageDto[] }>("/message/index", { auth: true });
    return (data.data ?? []).map(toMessage);
  });
}

export async function fetchUnreadCount(): Promise<number> {
  return degrade("未读消息数", 0, async () => {
    const data = await request<{ count?: unknown }>("/message/unread", { auth: true });
    return num(data.count as number | undefined);
  });
}

export async function markMessagesRead(): Promise<boolean> {
  try {
    await request<Record<string, unknown>>("/message/read", { auth: true, method: "POST", body: {} });
    return true;
  } catch {
    return false;
  }
}

/** 售后单 DTO。 */
export interface AftersaleDto {
  id?: number;
  order_id?: number;
  goods_name?: string;
  name?: string;
  image?: string;
  type?: string;
  type_name?: string;
  aftersale_type_name?: string;
  reason?: string;
  status?: number;
  status_name?: string;
  add_time?: string;
  refund_amount?: number;
}

export interface AftersaleItem {
  id: number;
  order_id: number;
  goods_name: string;
  image: string;
  type: string;
  type_name: string;
  reason: string;
  status: number;
  status_name: string;
  add_time?: string;
  refund_amount?: number;
}

export function toAftersale(dto: AftersaleDto): AftersaleItem {
  return {
    id: num(dto.id),
    order_id: num(dto.order_id),
    goods_name: dto.goods_name ?? dto.name ?? "",
    image: dto.image ?? "",
    type: dto.type ?? "",
    type_name: dto.type_name ?? dto.aftersale_type_name ?? "",
    reason: dto.reason ?? "",
    status: num(dto.status),
    status_name: dto.status_name ?? "",
    add_time: dto.add_time,
    refund_amount: dto.refund_amount,
  };
}

/** 统一解包后端 { data: { data: [...] } } / { data: [...] } / [...] 三种分页形态。 */
function unwrapList<T>(res: unknown): T[] {
  const inner = (res as { data?: unknown }).data;
  if (inner && typeof inner === "object" && Array.isArray((inner as { data?: unknown }).data)) {
    return (inner as { data: T[] }).data;
  }
  return Array.isArray(inner) ? (inner as T[]) : [];
}

export async function fetchAftersales(): Promise<AftersaleItem[]> {
  return degrade("售后列表", [], async () => {
    const res = await request<Record<string, unknown>>("/aftersale/index", { auth: true });
    return unwrapList<AftersaleDto>(res).map(toAftersale);
  });
}

export async function applyAftersale(input: {
  type: string;
  order_detail_id: number;
  reason: string;
  refund_amount?: number;
}): Promise<boolean> {
  try {
    const body: Record<string, unknown> = {
      type: input.type,
      order_detail_id: input.order_detail_id,
      reason: input.reason,
    };
    if (typeof input.refund_amount === "number") body.refund_amount = input.refund_amount;
    await request<Record<string, unknown>>("/aftersale/save", { auth: true, method: "POST", body });
    return true;
  } catch {
    return false;
  }
}

export async function cancelAftersale(id: number): Promise<boolean> {
  try {
    await request<Record<string, unknown>>("/aftersale/cancel", { auth: true, method: "POST", body: { id } });
    return true;
  } catch {
    return false;
  }
}

/** 浏览历史 DTO。 */
export interface BrowseDto {
  id?: number;
  goods_id?: number;
  goods_name?: string;
  name?: string;
  image?: string;
  price?: number;
  add_time?: string;
}

export interface BrowseItem {
  id: number;
  goods_id: number;
  name: string;
  image: string;
  price?: number;
  add_time?: string;
}

export function toBrowse(dto: BrowseDto): BrowseItem {
  return {
    id: num(dto.id),
    goods_id: num(dto.goods_id),
    name: dto.goods_name ?? dto.name ?? "",
    image: dto.image ?? "",
    price: dto.price,
    add_time: dto.add_time,
  };
}

export async function fetchBrowseHistory(): Promise<BrowseItem[]> {
  return degrade("浏览历史", [], async () => {
    const res = await request<Record<string, unknown>>("/browse/index", { auth: true });
    return unwrapList<BrowseDto>(res).map(toBrowse);
  });
}

/** 积分流水 DTO。 */
export interface IntegralDto {
  id?: number;
  change_type?: string;
  type?: string;
  integral?: number;
  add_time?: string;
  remark?: string;
}

export interface IntegralItem {
  id: number;
  change_type: string;
  integral: number;
  add_time?: string;
  remark?: string;
}

export function toIntegral(dto: IntegralDto): IntegralItem {
  return {
    id: num(dto.id),
    change_type: dto.change_type ?? dto.type ?? "",
    integral: num(dto.integral),
    add_time: dto.add_time,
    remark: dto.remark,
  };
}

export async function fetchIntegral(): Promise<IntegralItem[]> {
  // 后端 /user/integral 为空壳桩，暂返回本地空数据；后端实现后恢复 request 调用
  return [];
}
