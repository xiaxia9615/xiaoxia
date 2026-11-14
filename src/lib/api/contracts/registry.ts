/**
 * 接口契约登记表（只声明事实，不发起任何请求、不参与运行时逻辑）。
 *
 * 目的：把散落在各领域模块里的接口事实集中成一份可核对、可机检的清单，
 * 便于与后端（Laravel）逐条比对，也为 CI 提供「契约漂移」的检测点。
 *
 * 约束：
 * - 类型一律从原文件 import，不重写、不复制；
 * - 本文件不被 index.ts 再导出，避免与 barrel 形成循环；
 * - 只记录代码里已存在的事实，代码中没有的信息标 TODO，不做推测。
 */

import type { CategoryId, Product } from "@/data/products";
import type { Address, AddressInput } from "../address";
import type { Article, ArticleCategory, ArticleCategoryDto, ArticleDto } from "../article";
import type { CartItem, CartItemDto } from "../cart";
import type { CommentDto, FavoriteDto, GoodsCategoryDto, GoodsDto, GoodsPageDto } from "../goods";
import type { AftersaleDto, AftersaleItem, BrowseDto, BrowseItem, IntegralDto, IntegralItem, MessageDto, MessageItem } from "../misc";
import type { OrderDetailData, OrderDetailDto, OrderDto, OrderItem } from "../orders";
import type { BuyAddData, CheckoutData, PaySubmitData } from "../pay";
import type { User } from "../user";

/**
 * 已具名类型索引：把登记表里引用的类型名绑定到真实类型上。
 * 任何类型被改名、移动或删除，本文件会编译失败 —— 契约漂移的第一道报警。
 */
export interface ContractTypeIndex {
  CategoryId: CategoryId;
  Product: Product;
  Address: Address;
  AddressInput: AddressInput;
  Article: Article;
  ArticleCategory: ArticleCategory;
  ArticleCategoryDto: ArticleCategoryDto;
  ArticleDto: ArticleDto;
  CartItem: CartItem;
  CartItemDto: CartItemDto;
  CommentDto: CommentDto;
  FavoriteDto: FavoriteDto;
  GoodsCategoryDto: GoodsCategoryDto;
  GoodsDto: GoodsDto;
  GoodsPageDto: GoodsPageDto;
  AftersaleDto: AftersaleDto;
  AftersaleItem: AftersaleItem;
  BrowseDto: BrowseDto;
  BrowseItem: BrowseItem;
  IntegralDto: IntegralDto;
  IntegralItem: IntegralItem;
  MessageDto: MessageDto;
  MessageItem: MessageItem;
  OrderDetailData: OrderDetailData;
  OrderDetailDto: OrderDetailDto;
  OrderDto: OrderDto;
  OrderItem: OrderItem;
  BuyAddData: BuyAddData;
  CheckoutData: CheckoutData;
  PaySubmitData: PaySubmitData;
  User: User;
}

/** 具名类型名（编译期受限，防止登记表里写错名字） */
export type KnownTypeName = keyof ContractTypeIndex;

/**
 * 参数/返回类型的引用方式：
 * - named：代码中存在具名类型
 * - inline：代码中以匿名内联类型声明（原样记录，不算缺失）
 * - none：该接口确实没有参数/返回
 * - unexported：类型在代码中存在，但原文件未导出，无法 import（原文件按约定不动）
 * - todo：代码中定位不到，待补
 */
export type ContractTypeRef =
  | { kind: "named"; name: KnownTypeName }
  | { kind: "inline"; shape: string }
  | { kind: "none" }
  | { kind: "unexported"; name: string; reason: string }
  | { kind: "todo" };

/** 请求方式 */
export type ContractMethod = "GET" | "POST" | "TODO";

export interface ApiContract {
  /** 接口名（原文件导出的函数名） */
  fn: string;
  /** 所属模块文件 */
  source: string;
  method: ContractMethod;
  /** 请求路径；条件路径按代码原样记录在 note */
  path: string;
  /** 请求参数类型引用 */
  request: ContractTypeRef;
  /** 返回类型引用 */
  response: ContractTypeRef;
  /** 是否主链路接口 */
  critical: boolean;
  /** 事实补充：内联形态、条件分支、鉴权、数据源等 */
  note?: string;
}

/** 主链路：这 5 个接口必须有后端与联调验证 */
export const CRITICAL_PATHS = [
  "/login",
  "/goods/index",
  "/cart/index",
  "/buy/index",
  "/pay/submit",
] as const;

export type CriticalPath = (typeof CRITICAL_PATHS)[number];

const CRITICAL = new Set<string>(CRITICAL_PATHS);

export const API_CONTRACTS: ApiContract[] = [
  // ---------- 账号（user.ts） ----------
  {
    fn: "fetchPublicKey",
    source: "lib/api/user.ts",
    method: "GET",
    path: "/security/publickey",
    request: { kind: "none" },
    response: {
      kind: "unexported",
      name: "PublicKeyData",
      reason: "user.ts 内定义但未 export，按「原文件不动」约定无法 import",
    },
    critical: false,
    note: "登录/改密/重置密码的 RSA 加密前置依赖",
  },
  {
    fn: "login",
    source: "lib/api/user.ts",
    method: "POST",
    path: "/login",
    request: { kind: "inline", shape: "(username: string, password: Promise<string> | string)" },
    response: { kind: "inline", shape: "Promise<{ token: string; user: User }>" },
    critical: true,
    note: "请求体为 { username, password }，并附带 encrypt: \"rsa\" 标识",
  },
  {
    fn: "register",
    source: "lib/api/user.ts",
    method: "POST",
    path: "/register",
    request: { kind: "inline", shape: "(username: string, password: string)" },
    response: { kind: "named", name: "User" },
    critical: false,
  },
  {
    fn: "fetchProfile",
    source: "lib/api/user.ts",
    method: "GET",
    path: "/user/profile",
    request: { kind: "none" },
    response: { kind: "named", name: "User" },
    critical: false,
    note: "需登录",
  },
  {
    fn: "updateProfile",
    source: "lib/api/user.ts",
    method: "POST",
    path: "/user/update",
    request: {
      kind: "inline",
      shape: "Partial<{ nickname; avatar; sex; birthday; province; city; district }>",
    },
    response: { kind: "named", name: "User" },
    critical: false,
    note: "需登录",
  },
  {
    fn: "changePassword",
    source: "lib/api/user.ts",
    method: "POST",
    path: "/user/password",
    request: { kind: "inline", shape: "(oldPassword: string, newPassword: string)" },
    response: { kind: "inline", shape: "string" },
    critical: false,
    note: "需登录；两个密码均先经 RSA 加密",
  },
  {
    fn: "sendVerifyCode",
    source: "lib/api/user.ts",
    method: "POST",
    path: "/verify/send",
    request: { kind: "inline", shape: "(mobile: string, scene: \"find_password\" = \"find_password\")" },
    response: { kind: "none" },
    critical: false,
  },
  {
    fn: "resetPassword",
    source: "lib/api/user.ts",
    method: "POST",
    path: "/user/reset-password",
    request: { kind: "inline", shape: "{ phone: string; code: string; newPassword: string }" },
    response: { kind: "none" },
    critical: false,
    note: "新密码经 RSA 加密后提交",
  },

  // ---------- 商品与互动（goods.ts） ----------
  {
    fn: "fetchProducts",
    source: "lib/api/goods.ts",
    method: "GET",
    path: "/goods/index",
    request: { kind: "inline", shape: "{ page?: number; limit?: number; category?: CategoryId }" },
    response: { kind: "inline", shape: "Promise<{ list: Product[]; total: number }>" },
    critical: true,
    note: "分类 id 映射见 CATEGORY_TO_API；接口失败回退本地演示目录",
  },
  {
    fn: "fetchProduct",
    source: "lib/api/goods.ts",
    method: "GET",
    path: "/goods/detail",
    request: { kind: "inline", shape: "(id: string)" },
    response: { kind: "inline", shape: "Promise<Product | undefined>" },
    critical: false,
    note: "失败回退本地数据",
  },
  {
    fn: "fetchCategories",
    source: "lib/api/goods.ts",
    method: "GET",
    path: "/goods/category",
    request: { kind: "none" },
    response: { kind: "named", name: "GoodsCategoryDto" },
    critical: false,
  },
  {
    fn: "fetchFavorites",
    source: "lib/api/goods.ts",
    method: "GET",
    path: "/favor/index",
    request: { kind: "inline", shape: "{ page?: number; limit?: number }" },
    response: { kind: "named", name: "FavoriteDto" },
    critical: false,
    note: "需登录；返回 FavoriteDto[] 经 toFavorite 映射",
  },
  {
    fn: "fetchFavorStatus",
    source: "lib/api/goods.ts",
    method: "GET",
    path: "/favor/status",
    request: { kind: "inline", shape: "(goodsId: number)" },
    response: { kind: "inline", shape: "Promise<boolean>" },
    critical: false,
    note: "需登录；读取响应字段 is_favor",
  },
  {
    fn: "setFavorite",
    source: "lib/api/goods.ts",
    method: "POST",
    path: "/favor/save",
    request: { kind: "inline", shape: "(goodsId: number, favor: boolean)" },
    response: { kind: "inline", shape: "Promise<boolean>" },
    critical: false,
    note: "条件路径：favor 为 false 时改用 /favor/delete；需登录",
  },
  {
    fn: "fetchComments",
    source: "lib/api/goods.ts",
    method: "GET",
    path: "/comments/index",
    request: { kind: "inline", shape: "(goodsId: number)" },
    response: { kind: "named", name: "CommentDto" },
    critical: false,
    note: "返回 CommentDto[] 经 toComment 映射",
  },

  // ---------- 购物车（cart.ts） ----------
  {
    fn: "fetchCart",
    source: "lib/api/cart.ts",
    method: "GET",
    path: "/cart/index",
    request: { kind: "none" },
    response: { kind: "inline", shape: "Promise<CartItem[]>" },
    critical: true,
    note: "需登录；兼容 list / data / 裸数组三种响应形状",
  },
  {
    fn: "addCartItem",
    source: "lib/api/cart.ts",
    method: "POST",
    path: "/cart/save",
    request: { kind: "inline", shape: "(goodsId: number, quantity: number)" },
    response: { kind: "none" },
    critical: false,
    note: "需登录；请求体 { goods_id, quantity }",
  },
  {
    fn: "updateCartQuantity",
    source: "lib/api/cart.ts",
    method: "POST",
    path: "/cart/save",
    request: { kind: "inline", shape: "(cartId: number, quantity: number)" },
    response: { kind: "none" },
    critical: false,
    note: "需登录；与 addCartItem 同一路径，请求体 { id, quantity }",
  },
  {
    fn: "deleteCartItem",
    source: "lib/api/cart.ts",
    method: "POST",
    path: "/cart/delete",
    request: { kind: "inline", shape: "(cartId: number)" },
    response: { kind: "none" },
    critical: false,
    note: "需登录；请求体 { id }",
  },

  // ---------- 地址（address.ts） ----------
  {
    fn: "fetchAddresses",
    source: "lib/api/address.ts",
    method: "GET",
    path: "/address/index",
    request: { kind: "none" },
    response: { kind: "named", name: "Address" },
    critical: false,
    note: "需登录；返回 Address[]，经 toAddress 过滤无效项",
  },
  {
    fn: "createAddress",
    source: "lib/api/address.ts",
    method: "POST",
    path: "/address/save",
    request: { kind: "named", name: "AddressInput" },
    response: { kind: "none" },
    critical: false,
    note: "需登录",
  },
  {
    fn: "updateAddress",
    source: "lib/api/address.ts",
    method: "POST",
    path: "/address/save",
    request: { kind: "named", name: "AddressInput" },
    response: { kind: "none" },
    critical: false,
    note: "需登录；请求体为 { id, ...input }，参数含 addressId 与 AddressInput",
  },
  {
    fn: "deleteAddress",
    source: "lib/api/address.ts",
    method: "POST",
    path: "/address/delete",
    request: { kind: "inline", shape: "(addressId: number)" },
    response: { kind: "none" },
    critical: false,
    note: "需登录；请求体 { id }",
  },
  {
    fn: "setDefaultAddress",
    source: "lib/api/address.ts",
    method: "POST",
    path: "/address/default",
    request: { kind: "inline", shape: "(addressId: number)" },
    response: { kind: "none" },
    critical: false,
    note: "需登录；请求体 { id }",
  },

  // ---------- 订单（orders.ts） ----------
  {
    fn: "fetchOrders",
    source: "lib/api/orders.ts",
    method: "GET",
    path: "/order/index",
    request: { kind: "inline", shape: "{ page?: number; limit?: number }" },
    response: { kind: "inline", shape: "Promise<{ list: OrderItem[]; total: number }>" },
    critical: false,
    note: "需登录；兼容 list / data / 裸数组三种响应形状",
  },
  {
    fn: "cancelOrder",
    source: "lib/api/orders.ts",
    method: "POST",
    path: "/order/cancel",
    request: { kind: "inline", shape: "(orderId: number)" },
    response: { kind: "inline", shape: "Promise<boolean>" },
    critical: false,
    note: "需登录；请求体 { id }",
  },
  {
    fn: "fetchOrderDetail",
    source: "lib/api/orders.ts",
    method: "GET",
    path: "/order/detail",
    request: { kind: "inline", shape: "(orderId: number)" },
    response: { kind: "named", name: "OrderDetailData" },
    critical: false,
    note: "需登录；订单不存在时返回 null",
  },
  {
    fn: "deleteOrder",
    source: "lib/api/orders.ts",
    method: "POST",
    path: "/order/delete",
    request: { kind: "inline", shape: "(orderId: number)" },
    response: { kind: "inline", shape: "Promise<boolean>" },
    critical: false,
    note: "需登录；请求体 { id }",
  },

  // ---------- 交易与支付（pay.ts） ----------
  {
    fn: "fetchCheckout",
    source: "lib/api/pay.ts",
    method: "POST",
    path: "/buy/index",
    request: { kind: "inline", shape: "{ buyType: \"cart\"; ids: number[] }" },
    response: { kind: "named", name: "CheckoutData" },
    critical: true,
    note: "需登录；结算预览，返回 items / addresses / payments",
  },
  {
    fn: "buyAdd",
    source: "lib/api/pay.ts",
    method: "POST",
    path: "/buy/add",
    request: {
      kind: "inline",
      shape:
        "{ buyType: \"cart\"; ids: number[]; addressId: number; paymentId?: number } | { buyType: \"goods\"; goodsData: Array<{ goods_id: number; stock?: number }>; addressId: number; paymentId?: number }",
    },
    response: { kind: "named", name: "BuyAddData" },
    critical: false,
    note: "需登录；下单，购物车与直购两种模式",
  },
  {
    fn: "paySubmit",
    source: "lib/api/pay.ts",
    method: "POST",
    path: "/pay/submit",
    request: { kind: "inline", shape: "{ orderId: number; payment: string; client?: \"app\" | \"mini\" | \"pc\" }" },
    response: { kind: "named", name: "PaySubmitData" },
    critical: true,
    note: "需登录；返回 pay_form / order_str / pay_url 三类支付载体",
  },
  {
    fn: "payQuery",
    source: "lib/api/pay.ts",
    method: "GET",
    path: "/pay/query",
    request: { kind: "inline", shape: "(orderId: number)" },
    response: {
      kind: "inline",
      shape: "Promise<{ order_id; order_sn; order_status; status_name; pay_status; pay_time; amount }>",
    },
    critical: false,
    note: "需登录；参数 order_id",
  },

  // ---------- 文章（article.ts） ----------
  {
    fn: "fetchArticles",
    source: "lib/api/article.ts",
    method: "GET",
    path: "/article/index",
    request: { kind: "inline", shape: "{ page?: number; limit?: number; category_id?: number }" },
    response: { kind: "named", name: "Article" },
    critical: false,
    note: "失败降级为空列表",
  },
  {
    fn: "fetchArticleCategories",
    source: "lib/api/article.ts",
    method: "GET",
    path: "/article/category",
    request: { kind: "none" },
    response: { kind: "named", name: "ArticleCategory" },
    critical: false,
    note: "失败降级为空列表",
  },
  {
    fn: "fetchArticleDetail",
    source: "lib/api/article.ts",
    method: "GET",
    path: "/article/detail",
    request: { kind: "inline", shape: "(id: number)" },
    response: { kind: "named", name: "Article" },
    critical: false,
    note: "失败降级为 null",
  },

  // ---------- 消息 / 售后 / 足迹 / 积分（misc.ts） ----------
  {
    fn: "fetchMessages",
    source: "lib/api/misc.ts",
    method: "GET",
    path: "/message/index",
    request: { kind: "none" },
    response: { kind: "named", name: "MessageItem" },
    critical: false,
    note: "需登录；失败降级为空列表",
  },
  {
    fn: "fetchUnreadCount",
    source: "lib/api/misc.ts",
    method: "GET",
    path: "/message/unread",
    request: { kind: "none" },
    response: { kind: "inline", shape: "Promise<number>" },
    critical: false,
    note: "需登录；读取响应字段 count，失败降级为 0",
  },
  {
    fn: "markMessagesRead",
    source: "lib/api/misc.ts",
    method: "POST",
    path: "/message/read",
    request: { kind: "none" },
    response: { kind: "inline", shape: "Promise<boolean>" },
    critical: false,
    note: "需登录；请求体为空对象",
  },
  {
    fn: "fetchAftersales",
    source: "lib/api/misc.ts",
    method: "GET",
    path: "/aftersale/index",
    request: { kind: "none" },
    response: { kind: "named", name: "AftersaleItem" },
    critical: false,
    note: "需登录；失败降级为空列表",
  },
  {
    fn: "applyAftersale",
    source: "lib/api/misc.ts",
    method: "POST",
    path: "/aftersale/save",
    request: { kind: "inline", shape: "{ type: string; order_detail_id: number; reason: string; refund_amount?: number }" },
    response: { kind: "inline", shape: "Promise<boolean>" },
    critical: false,
    note: "需登录",
  },
  {
    fn: "cancelAftersale",
    source: "lib/api/misc.ts",
    method: "POST",
    path: "/aftersale/cancel",
    request: { kind: "inline", shape: "(id: number)" },
    response: { kind: "inline", shape: "Promise<boolean>" },
    critical: false,
    note: "需登录；请求体 { id }",
  },
  {
    fn: "fetchBrowseHistory",
    source: "lib/api/misc.ts",
    method: "GET",
    path: "/browse/index",
    request: { kind: "none" },
    response: { kind: "named", name: "BrowseItem" },
    critical: false,
    note: "需登录；失败降级为空列表",
  },
  {
    fn: "fetchIntegral",
    source: "lib/api/misc.ts",
    method: "GET",
    path: "/user/integral",
    request: { kind: "none" },
    response: { kind: "named", name: "IntegralItem" },
    critical: false,
    note: "需登录；失败降级为空列表",
  },

];

/** 主链路接口（critical: true） */
export const CRITICAL_CONTRACTS: ApiContract[] = API_CONTRACTS.filter((c) => CRITICAL.has(c.path));

/** 登记表中仍待补的信息（path 或类型未在代码中定位到） */
export const TODO_ENTRIES: ApiContract[] = API_CONTRACTS.filter(
  (c) => c.method === "TODO" || c.path === "TODO" || c.request.kind === "todo" || c.response.kind === "todo",
);

/** 类型存在于代码中但原文件未导出，无法 import 的条目（待原文件显式导出后收敛） */
export const UNEXPORTED_TYPE_ENTRIES: ApiContract[] = API_CONTRACTS.filter(
  (c) => c.request.kind === "unexported" || c.response.kind === "unexported",
);

/** 路径与类型都已在代码中定位到（未被标记 TODO）的条目数 */
export const RESOLVED_COUNT = API_CONTRACTS.length - TODO_ENTRIES.length;
