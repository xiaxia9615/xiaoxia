import { expect, test } from "@playwright/test";
import { mockApi } from "./support/api-mock";

test.describe("订单列表", () => {
  test("后端不可用时报错并给出重新加载入口", async ({ page }) => {
    await mockApi(page, { failures: { "/order/index": "服务暂时不可用" } });

    await page.goto("/#/orders");

    await expect(page.getByText("订单加载失败")).toBeVisible();
    await expect(page.getByRole("button", { name: "重新加载" })).toBeVisible();
  });

  test("没有订单时展示空态与选购入口", async ({ page }) => {
    await mockApi(page, { orders: [] });

    await page.goto("/#/orders");

    await expect(page.getByText("还没有订单")).toBeVisible();
    await expect(page.getByRole("link", { name: "去选购" })).toBeVisible();
  });

  test("待付款订单展示支付按钮", async ({ page }) => {
    await mockApi(page, {
      orders: [
        {
          id: 9001,
          order_sn: "E2E9001",
          order_status: 0,
          goods_name: "陶土马克杯",
          quantity: 1,
          total_amount: "128",
        },
      ],
    });

    await page.goto("/#/orders");

    await expect(page.getByText("E2E9001")).toBeVisible();
    await expect(page.getByRole("button", { name: "去支付" })).toBeVisible();
  });
});

test.describe("消息中心", () => {
  test("接口失败时展示失败态而非空态", async ({ page }) => {
    await mockApi(page, { failures: { "/message/index": "网络异常" } });

    await page.goto("/#/messages");

    await expect(page.getByText("消息加载失败")).toBeVisible();
    await expect(page.getByText("暂无消息")).toHaveCount(0);
  });
});
