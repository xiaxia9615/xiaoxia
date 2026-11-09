import type { Page } from "@playwright/test";

/** 后端统一响应包装：{ code, msg, data } */
export function ok<T>(data: T, msg = "success") {
  return { code: 0, msg, data };
}

export function fail(msg: string, code = 1) {
  return { code, msg, data: null };
}

interface ApiMockOptions {
  /** /goods/index 返回的商品列表 */
  products?: unknown[];
  /** /order/index 返回的订单列表 */
  orders?: unknown[];
  /** /cart/index 返回的购物车列表 */
  cart?: unknown[];
  /** 是否处于登录态（本地令牌） */
  loggedIn?: boolean;
  /** 指定接口返回业务错误，键为路径片段 */
  failures?: Record<string, string>;
}

/** 演示商品，字段与 lib/api 的映射函数契约一致 */
export const DEMO_PRODUCT = {
  id: 101,
  name: "陶土马克杯",
  description: "手工陶土，握感温润",
  image: "https://example.com/mug.jpg",
  price: "128",
  original_price: "158",
  category_id: 1,
  tags: ["陶土", "杯"],
};

/**
 * 拦截后端接口。测试不依赖真实服务，保证 e2e 在 CI 中稳定可跑。
 * 未匹配 /api/** 的请求一律返回空成功响应，避免测试因外部依赖抖动。
 */
export async function mockApi(page: Page, options: ApiMockOptions = {}) {
  const { products = [DEMO_PRODUCT], orders = [], cart = [], loggedIn = true, failures = {} } =
    options;

  if (loggedIn) {
    await page.addInitScript(() => {
      window.localStorage.setItem("xiaoxia.token", "e2e-token");
    });
  }

  await page.route("**/api/**", async (route) => {
    const url = route.request().url();
    const path = new URL(url).pathname.replace(/^\/api/, "");

    const failureKey = Object.keys(failures).find((key) => path.includes(key));
    if (failureKey) {
      await route.fulfill({ json: fail(failures[failureKey]) });
      return;
    }

    if (path.includes("/goods/index")) {
      await route.fulfill({ json: ok({ list: products, total: products.length }) });
      return;
    }
    if (path.includes("/goods/detail") || path.includes("/goods/info")) {
      await route.fulfill({ json: ok(products[0]) });
      return;
    }
    if (path.includes("/order/index")) {
      await route.fulfill({ json: ok({ list: orders, total: orders.length }) });
      return;
    }
    if (path.includes("/cart/index")) {
      await route.fulfill({ json: ok({ list: cart }) });
      return;
    }
    if (path.includes("/activity_participants")) {
      await route.fulfill({ json: ok({ rows: [], total: 0 }) });
      return;
    }
    if (path.includes("/user/info") || path.includes("/user/index")) {
      await route.fulfill({
        json: ok({ id: 1, username: "e2e", nickname: "端到端用户", avatar: "" }),
      });
      return;
    }

    await route.fulfill({ json: ok({ list: [], total: 0 }) });
  });
}
