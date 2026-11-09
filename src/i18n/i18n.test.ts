import { describe, expect, it } from "vitest";
import en from "./en";
import zh from "./zh";

/** 递归收集字典的所有键路径，用于校验中英文键位是否一致 */
function keyPaths(value: unknown, prefix = ""): string[] {
  if (typeof value !== "object" || value === null) return [prefix];
  return Object.entries(value).flatMap(([key, child]) =>
    keyPaths(child, prefix ? `${prefix}.${key}` : key),
  );
}

describe("i18n 字典", () => {
  it("中英文键位完全一致（避免切换语言后出现空缺）", () => {
    expect(keyPaths(en).sort()).toEqual(keyPaths(zh).sort());
  });

  it("不存在空文案", () => {
    for (const path of keyPaths(zh)) {
      const value = path
        .split(".")
        .reduce<unknown>((acc, key) => (acc as Record<string, unknown>)[key], zh);
      expect(typeof value, path).toBe("string");
      expect((value as string).trim(), path).not.toBe("");
    }
  });

  it("英文文案不是中文占位（防止未翻译直接复制）", () => {
    for (const path of keyPaths(en)) {
      const value = path
        .split(".")
        .reduce<unknown>((acc, key) => (acc as Record<string, unknown>)[key], en);
      expect(value as string, path).not.toMatch(/[\u4e00-\u9fa5]/);
    }
  });
});
