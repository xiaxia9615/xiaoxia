import { PRODUCTS, type CategoryId, type Product } from "@/data/products";
import { num, reportFallback, request } from "./request";

/**
 * 后端商品 DTO —— 显式声明接口返回字段，作为前后端契约。
 * 后端字段变更时只需改这里与 toProduct，页面层不受影响。
 */
export interface GoodsDto {
  id: number;
  name: string;
  image: string;
  images?: string[];
  price: number | string;
  original_price?: number | string;
  stock?: number;
  sales?: number;
  category_id?: number;
  is_recommend?: number;
  add_time?: number;
  content?: string;
  sub_title?: string;
  unit?: string;
  specs?: unknown[];
}

export interface GoodsPageDto {
  page: number;
  page_size: number;
  total: number;
  total_pages: number;
  data: GoodsDto[];
}

export interface GoodsCategoryDto {
  id: number;
  name: string;
}

/** 分类映射：真实分类 id -> 前端分类。未知 id 归入 decor。 */
const CATEGORY_BY_API_ID: Record<number, Exclude<CategoryId, "all">> = {
  1: "drink",
  2: "home",
  3: "decor",
};

/** 前端分类 -> 真实接口分类 id。 */
const CATEGORY_TO_API: Record<Exclude<CategoryId, "all">, number | undefined> = {
  drink: 1,
  home: 2,
  decor: 3,
};

/** DTO -> 页面商品模型（显式字段映射，含兜底）。 */
export function toProduct(dto: GoodsDto): Product {
  const price = num(dto.price);
  return {
    id: String(dto.id),
    name: dto.name,
    category: CATEGORY_BY_API_ID[dto.category_id ?? -1] ?? "decor",
    price,
    originalPrice: num(dto.original_price) || price,
    rating: 0,
    reviews: num(dto.sales),
    description: dto.sub_title || dto.name,
    detail: dto.content || dto.sub_title || dto.name,
    tags: Array.isArray(dto.specs) ? dto.specs.map((s) => String(s)) : [],
    image: dto.image,
    images: dto.images && dto.images.length > 0 ? dto.images : undefined,
    featured: Boolean(dto.is_recommend),
  };
}

/**
 * 商品列表。
 * - 已知接口分类 id 的分类直接透传给后端；
 * - 未知映射时拉全量后在前端按 category 过滤；
 * - 接口失败时回退本地静态目录（仅保证可浏览，不允许进入交易链路）。
 */
export async function fetchProducts(
  query: { page?: number; limit?: number; category?: CategoryId } = {},
): Promise<{ list: Product[]; total: number }> {
  const category = query.category && query.category !== "all" ? query.category : undefined;
  const apiCategoryId = category ? CATEGORY_TO_API[category] : undefined;
  try {
    const params: Record<string, unknown> = {
      page: query.page ?? 1,
      page_size: query.limit ?? 12,
    };
    if (apiCategoryId !== undefined) params.category_id = apiCategoryId;
    const data = await request<GoodsPageDto>("/goods/index", { params });
    const list = data.data.map(toProduct);
    const filtered = category && apiCategoryId === undefined
      ? list.filter((p) => p.category === category)
      : list;
    return { list: filtered, total: data.total };
  } catch (err) {
    reportFallback("商品列表", err);
    const list = category ? PRODUCTS.filter((p) => p.category === category) : PRODUCTS;
    return { list, total: list.length };
  }
}

/** 商品详情。接口失败回退本地目录（本地 id 为字符串 slug，仅演示数据）。 */
export async function fetchProduct(id: string): Promise<Product | undefined> {
  const local = PRODUCTS.find((p) => p.id === id);
  try {
    const data = await request<GoodsDto>("/goods/detail", { params: { id } });
    return toProduct(data);
  } catch (err) {
    reportFallback("商品详情", err);
    return local;
  }
}

/** 商品分类。 */
export async function fetchCategories(): Promise<GoodsCategoryDto[]> {
  try {
    const data = await request<{ category: GoodsCategoryDto[] }>("/goods/category");
    return data.category ?? [];
  } catch {
    return [];
  }
}

/** 收藏条目 DTO。 */
export interface FavoriteDto {
  id?: number;
  goods_id?: number;
  name?: string;
  goods_name?: string;
  image?: string;
  price?: number;
  add_time?: string;
}

export interface FavoriteItem {
  id: number;
  goods_id: number;
  name: string;
  image?: string;
  price?: number;
  add_time?: string;
}

export function toFavorite(dto: FavoriteDto): FavoriteItem | null {
  const goodsId = num(dto.goods_id);
  if (goodsId <= 0) return null;
  return {
    id: num(dto.id),
    goods_id: goodsId,
    name: dto.name ?? dto.goods_name ?? "",
    image: dto.image,
    price: dto.price,
    add_time: dto.add_time,
  };
}

export async function fetchFavorites(query: { page?: number; limit?: number } = {}): Promise<FavoriteItem[]> {
  const data = await request<{ data?: FavoriteDto[] }>("/favor/index", {
    auth: true,
    params: { page: query.page ?? 1, page_size: query.limit ?? 20 },
  });
  return (data.data ?? [])
    .map(toFavorite)
    .filter((f): f is FavoriteItem => f !== null);
}

export async function fetchFavorStatus(goodsId: number): Promise<boolean> {
  try {
    const data = await request<{ is_favor?: unknown }>("/favor/status", {
      auth: true,
      params: { id: goodsId },
    });
    return Number(data.is_favor) === 1;
  } catch {
    return false;
  }
}

export async function setFavorite(goodsId: number, favor: boolean): Promise<boolean> {
  try {
    await request<Record<string, unknown>>(favor ? "/favor/save" : "/favor/delete", {
      auth: true,
      method: "POST",
      body: { goods_id: goodsId },
    });
    return true;
  } catch {
    return false;
  }
}

/** 评论 DTO。 */
export interface CommentDto {
  id?: number;
  user_name?: string;
  nickname?: string;
  avatar?: string;
  content?: string;
  reply?: string;
  score?: number;
  images?: string[];
  add_time?: string;
}

export interface CommentItem {
  id: number;
  user_name: string;
  avatar?: string;
  content: string;
  score?: number;
  images?: string[];
  add_time?: string;
}

export function toComment(dto: CommentDto): CommentItem {
  return {
    id: num(dto.id),
    user_name: dto.user_name ?? dto.nickname ?? "",
    avatar: dto.avatar,
    content: dto.content ?? dto.reply ?? "",
    score: dto.score,
    images: Array.isArray(dto.images) ? dto.images : undefined,
    add_time: dto.add_time,
  };
}

export async function fetchComments(goodsId: number): Promise<CommentItem[]> {
  try {
    const data = await request<{ data?: CommentDto[] }>("/comments/index", {
      params: { goods_id: goodsId },
    });
    return (data.data ?? []).map(toComment);
  } catch {
    return [];
  }
}
