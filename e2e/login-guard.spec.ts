import { expect, test } from "@playwright/test";
import { mockApi } from "./support/api-mock";

test.describe("登录守卫", () => {
  test("未登录访问受保护页面会跳到登录页并带上回跳参数", async ({ page }) => {
    await mockApi(page, { loggedIn: false });

    await page.goto("/#/orders");

    await expect(page).toHaveURL(/#\/login\?redirect=%2Forders/);
    await expect(page.getByRole("heading", { name: "小夏生活" })).toBeVisible();
  });

  test("未登录访问购物车仍可浏览本地购物车", async ({ page }) => {
    await mockApi(page, { loggedIn: false });

    await page.goto("/#/cart");

    await expect(page.getByRole("heading", { name: "购物车" })).toBeVisible();
  });
});

test.describe("商品浏览主线", () => {
  test("从首页进入商品列表并打开详情", async ({ page }) => {
    await mockApi(page);

    await page.goto("/#/products");
    await expect(page.getByText("陶土马克杯").first()).toBeVisible();

    await page.getByText("陶土马克杯").first().click();
    await expect(page).toHaveURL(/#\/products\/101/);
    await expect(page.getByRole("heading", { name: "陶土马克杯" })).toBeVisible();
  });
});
