import { expect, test } from "@playwright/test";
import { mockApi } from "./support/api-mock";

test.describe("开放参与提交", () => {
  test("打开参与页并进入表单", async ({ page }) => {
    await mockApi(page);

    await page.goto("/#/contribute");

    await expect(page.getByRole("heading", { level: 1 })).toBeVisible();
    await page.getByRole("button", { name: /参与|加入/ }).first().click();
    await expect(page.getByRole("dialog")).toBeVisible();
  });

  test("空称呼提交被字段校验拦下，不发请求", async ({ page }) => {
    let submitCount = 0;
    await mockApi(page);
    await page.route("**/activity_participants**", async (route) => {
      if (route.request().method() === "POST") submitCount += 1;
      await route.fulfill({ json: { code: 0, msg: "ok", data: { $id: "row-1" } } });
    });

    await page.goto("/#/contribute");
    await page.getByRole("button", { name: /参与|加入/ }).first().click();

    const dialog = page.getByRole("dialog");
    await dialog.getByRole("button", { name: /提交/ }).click();

    await expect(dialog.getByText("请填写称呼")).toBeVisible();
    expect(submitCount).toBe(0);
  });

  test("连续点击提交只发出一次写入请求（防重复提交）", async ({ page }) => {
    let submitCount = 0;
    await mockApi(page);
    await page.route("**/activity_participants**", async (route) => {
      if (route.request().method() === "POST") {
        submitCount += 1;
        await new Promise((resolve) => setTimeout(resolve, 400));
      }
      await route.fulfill({ json: { code: 0, msg: "ok", data: { $id: "row-1" } } });
    });

    await page.goto("/#/contribute");
    await page.getByRole("button", { name: /参与|加入/ }).first().click();

    const dialog = page.getByRole("dialog");
    await dialog.getByLabel("称呼").fill("端到端用户");
    const submit = dialog.getByRole("button", { name: /提交/ });
    await submit.click();
    await submit.click({ force: true }).catch(() => undefined);

    await expect
      .poll(() => submitCount, { timeout: 5000 })
      .toBe(1);
  });
});
