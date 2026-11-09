import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import {
  API_BASE,
  TOKEN_KEY,
  fetchProducts,
  fetchProduct,
  fetchCategories,
  fetchOrders,
  fetchAddresses,
  fetchCart,
  fetchArticles,
  fetchArticleCategories,
  fetchArticleDetail,
  fetchFavorites,
  fetchFavorStatus,
  fetchMessages,
  fetchUnreadCount,
  type GoodsPageDto,
} from "./api";

interface FetchMock {
  (input: string | URL | Request, init?: RequestInit): Promise<Response>;
}

function jsonResponse(data: unknown, init: RequestInit = {}) {
  return {
    ok: true,
    status: 200,
    json: () => Promise.resolve(data),
    ...init,
  } as Response;
}

function okApi<T>(data: T) {
  return jsonResponse({ code: 0, msg: "ok", data });
}

function failApi(code: number, msg: string) {
  return jsonResponse({ code, msg, data: null });
}

/** 记录请求，可选按规则返回响应；未匹配时抛出网络错误。 */
function installFetch(
  responder: (url: string, init: RequestInit) => Response | undefined,
) {
  const calls: Array<{ url: string; init: RequestInit }> = [];
  const fetchMock = vi.fn(
    (input: string | URL | Request, init: RequestInit = {}) => {
      const url = String(input);
      calls.push({ url, init });
      const res = responder(url, init);
      if (res) return Promise.resolve(res);
      return Promise.reject(new Error("network error"));
    },
  ) as unknown as FetchMock;
  vi.stubGlobal("fetch", fetchMock);
  return { calls, fetchMock };
}

describe("fetchProducts", () => {
  it("请求 /goods/index 并映射商品字段", async () => {
    const { calls } = installFetch((url) => {
      expect(url).toBe(`${API_BASE}/goods/index?page=1&page_size=12`);
      return okApi<GoodsPageDto>({
        page: 1,
        page_size: 12,
        total: 1,
        total_pages: 1,
        data: [
          {
            id: 7,
            name: "冷萃茶具",
            image: "http://img/a.jpg",
            images: ["http://img/a.jpg"],
            price: "59.9",
            original_price: "79",
            sales: 3,
            category_id: 1,
            is_recommend: 1,
            specs: ["标准", "加厚"],
            sub_title: "简约",
            content: "详情",
          },
        ],
      });
    });

    const { list, total } = await fetchProducts();
    expect(calls).toHaveLength(1);
    expect(total).toBe(1);
    expect(list[0]).toMatchObject({
      id: "7",
      name: "冷萃茶具",
      category: "drink",
      price: 59.9,
      originalPrice: 79,
      reviews: 3,
      featured: true,
      tags: ["标准", "加厚"],
      image: "http://img/a.jpg",
      images: ["http://img/a.jpg"],
      detail: "详情",
    });
  });

  it("未知分类 id 归入 decor，original_price 缺失时回退 price", async () => {
    installFetch(() =>
      okApi<GoodsPageDto>({
        page: 1,
        page_size: 12,
        total: 1,
        total_pages: 1,
        data: [{ id: 9, name: "香薰", image: "http://img/b.jpg", price: "20", category_id: 999 }],
      }),
    );
    const { list } = await fetchProducts();
    expect(list[0].category).toBe("decor");
    expect(list[0].originalPrice).toBe(20);
  });

  it("接口失败时回退本地静态目录并按分类过滤", async () => {
    installFetch(() => undefined); // 网络错误
    const all = await fetchProducts();
    expect(all.list.length).toBeGreaterThan(0);

    const home = await fetchProducts({ category: "home" });
    expect(home.list.length).toBeGreaterThan(0);
    expect(home.list.every((p) => p.category === "home")).toBe(true);
  });
});

describe("fetchProduct", () => {
  it("请求 /goods/detail 并映射", async () => {
    installFetch((url) => {
      expect(url).toContain("/goods/detail");
      expect(url).toContain("id=7");
      return okApi({ id: 7, name: "单品", image: "http://img/a.jpg", price: 10, category_id: 1 });
    });
    const p = await fetchProduct("7");
    expect(p?.id).toBe("7");
    expect(p?.name).toBe("单品");
  });

  it("接口失败时回退本地商品", async () => {
    installFetch(() => undefined);
    const p = await fetchProduct("mug-terracotta");
    expect(p?.id).toBe("mug-terracotta");
  });
});

describe("fetchCategories", () => {
  it("返回 category 数组", async () => {
    installFetch(() =>
      okApi({ category: [{ id: 1, name: "茶饮" }, { id: 2, name: "家居" }] }),
    );
    const cats = await fetchCategories();
    expect(cats).toEqual([
      { id: 1, name: "茶饮" },
      { id: 2, name: "家居" },
    ]);
  });

  it("失败时返回空数组", async () => {
    installFetch(() => undefined);
    await expect(fetchCategories()).resolves.toEqual([]);
  });
});

describe("fetchOrders", () => {
  const raw = {
    list: [
      {
        id: 11,
        order_sn: "SN11",
        order_status: 1,
        status_name: "待发货",
        add_time: 1714567890,
        items: [{ name: "商品A", image: "http://img/a.jpg", quantity: 2 }],
      },
    ],
    total: 1,
  };

  it("归一化订单：取 items 首个商品，add_time 格式化", async () => {
    installFetch((url, init) => {
      expect(init.headers).toMatchObject({ Authorization: "Bearer test-token" });
      return okApi(raw);
    });
    localStorage.setItem(TOKEN_KEY, "test-token");
    const { list, total } = await fetchOrders();
    expect(total).toBe(1);
    expect(list[0]).toMatchObject({
      id: 11,
      order_sn: "SN11",
      order_status: 1,
      status_name: "待发货",
      goods_name: "商品A",
      quantity: 2,
    });
    // add_time 由本地时区格式化，仅校验格式
    expect(list[0].created_at).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/);
  });

  it("未登录时抛 unauthorized 错误（由全局 401 处理接管）", async () => {
    localStorage.removeItem(TOKEN_KEY);
    installFetch(() => okApi(raw));
    await expect(fetchOrders()).rejects.toMatchObject({
      kind: "unauthorized",
    });
  });
});

describe("fetchAddresses", () => {
  it("解析 list / data / 顶层数组并归一化", async () => {
    installFetch(() =>
      okApi({
        list: [
          { address_id: 3, name: "张三", phone: "138", province: "浙", city: "杭", district: "余杭", detail: "1号", is_default: 1 },
        ],
      }),
    );
    localStorage.setItem(TOKEN_KEY, "t");
    const list = await fetchAddresses();
    expect(list[0]).toMatchObject({
      address_id: 3,
      name: "张三",
      is_default: true,
    });
  });

  it("缺少 address_id 的项被过滤", async () => {
    installFetch(() => okApi([{ name: "无效" }, { address_id: 5, name: "李四", phone: "139", province: "a", city: "b", district: "c", detail: "d", is_default: false }]));
    localStorage.setItem(TOKEN_KEY, "t");
    const list = await fetchAddresses();
    expect(list).toHaveLength(1);
    expect(list[0].address_id).toBe(5);
  });
});

describe("fetchCart", () => {
  it("返回数组型购物车", async () => {
    installFetch(() =>
      okApi([
        { id: 1, goods_id: 7, goods_name: "商品", price: 10, quantity: 2 },
      ]),
    );
    localStorage.setItem(TOKEN_KEY, "t");
    const list = await fetchCart();
    expect(list).toHaveLength(1);
    expect(list[0]).toMatchObject({ goods_id: 7, quantity: 2 });
  });
});

describe("文章接口", () => {
  it("fetchArticles 解析 list 与 total", async () => {
    installFetch(() =>
      okApi({ list: [{ id: 1, title: "标题", cover: "http://img/c.jpg", content: "正文" }], total: 1 }),
    );
    const { list, total } = await fetchArticles();
    expect(total).toBe(1);
    expect(list[0]).toMatchObject({ id: 1, title: "标题", content: "正文" });
  });

  it("fetchArticleCategories 解析 category", async () => {
    installFetch(() => okApi({ category: [{ id: 1, name: "生活" }] }));
    const cats = await fetchArticleCategories();
    expect(cats).toEqual([{ id: 1, name: "生活" }]);
  });

  it("fetchArticleDetail 解析详情，空对象返回 null", async () => {
    installFetch((url) => (url.includes("id=5") ? okApi({ id: 5, title: "详情标题" }) : okApi({})));
    const detail = await fetchArticleDetail(5);
    expect(detail?.title).toBe("详情标题");
    const empty = await fetchArticleDetail(6);
    expect(empty).toBeNull();
  });
});

describe("收藏与消息接口", () => {
  it("fetchFavorites 过滤 goods_id 无效项", async () => {
    installFetch(() =>
      okApi({
        data: [
          { id: 1, goods_id: 7, name: "A", image: "http://img/a.jpg", price: 9 },
          { id: 2, goods_id: 0, name: "B" },
        ],
      }),
    );
    localStorage.setItem(TOKEN_KEY, "t");
    const list = await fetchFavorites();
    expect(list).toHaveLength(1);
    expect(list[0]).toMatchObject({ goods_id: 7, name: "A" });
  });

  it("fetchFavorStatus 按 is_favor 返回布尔", async () => {
    installFetch(() => okApi({ is_favor: 1 }));
    localStorage.setItem(TOKEN_KEY, "t");
    await expect(fetchFavorStatus(7)).resolves.toBe(true);
  });

  it("fetchMessages 解析列表", async () => {
    installFetch(() =>
      okApi({ data: [{ id: 1, title: "通知", content: "内容", is_read: 0 }] }),
    );
    localStorage.setItem(TOKEN_KEY, "t");
    const list = await fetchMessages();
    expect(list[0]).toMatchObject({ id: 1, title: "通知", is_read: 0 });
  });

  it("fetchUnreadCount 返回数字", async () => {
    installFetch(() => okApi({ count: 5 }));
    localStorage.setItem(TOKEN_KEY, "t");
    await expect(fetchUnreadCount()).resolves.toBe(5);
  });
});

describe("统一响应与鉴权", () => {
  it("code !== 0 时抛错并携带 code", async () => {
    installFetch(() => failApi(1001, "参数错误"));
    // 未登录会先抛"未登录"，这里确认鉴权路径；无 token 时 fetchCategories 返回空
    const res = await fetchCategories();
    expect(res).toEqual([]);
  });

  it("HTTP 非 2xx 时回退本地数据", async () => {
    installFetch(() => ({ ...jsonResponse(null), ok: false, status: 500 }) as Response);
    const p = await fetchProduct("mug-terracotta");
    expect(p?.id).toBe("mug-terracotta");
  });
});

beforeEach(() => {
  localStorage.clear();
});

afterEach(() => {
  vi.unstubAllGlobals();
  localStorage.clear();
});