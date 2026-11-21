import { num, request } from "./request";
import type { paths } from "./schema";

/** 后端购物车项 DTO（显式契约声明）。 */
export interface CartItemDto {
  id?: number;
  goods_id?: number;
  goods_name?: string;
  image?: string;
  price?: number | string;
  quantity?: number;
  stock?: number;
}

/** 页面购物车项（id 为购物车记录 id，用于增删改）。 */
export interface CartItem {
  id?: number;
  goods_id?: number;
  goods_name?: string;
  image?: string;
  price?: number;
  quantity: number;
  stock?: number;
}

export function toCartItem(dto: CartItemDto): CartItem {
  return {
    id: dto.id,
    goods_id: dto.goods_id,
    goods_name: dto.goods_name,
    image: dto.image,
    price: dto.price === undefined ? undefined : num(dto.price),
    quantity: dto.quantity ?? 1,
    stock: dto.stock,
  };
}

/** Schema 请求体类型（cart 端点无 200 响应定义，响应类型暂保留手写，见待汇报清单） */
type CartSaveReq = paths["/api/v1/cart/save"]["post"]["requestBody"]["content"]["application/json"];
type CartDeleteReq = paths["/api/v1/cart/delete"]["post"]["requestBody"]["content"]["application/json"];

/** 购物车列表（需登录）。错误上抛由 React Query 呈现。 */
export async function fetchCart(): Promise<CartItem[]> {
  const data = await request<{ list?: CartItemDto[]; data?: CartItemDto[] } | CartItemDto[]>(
    "/cart/index",
    { auth: true },
  );
  const rawList = Array.isArray(data) ? data : (data.data ?? data.list ?? []);
  return rawList.map(toCartItem);
}

/** 加入购物车（需登录）。 */
export async function addCartItem(goodsId: number, quantity: number): Promise<void> {
  const body: CartSaveReq = { goods_id: goodsId, quantity };
  await request<null>("/cart/save", {
    method: "POST",
    auth: true,
    body,
  });
}

/** 更新购物车数量（需登录）。 */
export async function updateCartQuantity(
  cartId: number,
  quantity: number,
): Promise<void> {
  const body: CartSaveReq = { id: cartId, quantity };
  await request<null>("/cart/save", {
    method: "POST",
    auth: true,
    body,
  });
}

/** 删除购物车项（需登录）。 */
export async function deleteCartItem(cartId: number): Promise<void> {
  const body: CartDeleteReq = { id: cartId };
  await request<null>("/cart/delete", {
    method: "POST",
    auth: true,
    body,
  });
}
