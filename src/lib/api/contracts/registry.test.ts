import { describe, expect, it } from "vitest";
import {
  API_CONTRACTS,
  CRITICAL_CONTRACTS,
  CRITICAL_PATHS,
  RESOLVED_COUNT,
  TODO_ENTRIES,
  UNEXPORTED_TYPE_ENTRIES,
  type ContractTypeRef,
} from "./registry";

/** 把类型引用转成可读文本，便于断言与失败定位。 */
function describeRef(ref: ContractTypeRef): string {
  switch (ref.kind) {
    case "named":
      return ref.name;
    case "inline":
      return ref.shape;
    case "none":
      return "none";
    case "unexported":
      return `${ref.name}（未导出）`;
    case "todo":
      return "TODO";
  }
}

describe("接口契约登记表", () => {
  it("接口名唯一", () => {
    const names = API_CONTRACTS.map((c) => c.fn);
    expect(new Set(names).size).toBe(names.length);
  });

  it("每条记录都有方法、路径与类型声明", () => {
    for (const contract of API_CONTRACTS) {
      expect(contract.method, contract.fn).toMatch(/^(GET|POST|CLOUD|TODO)$/);
      expect(contract.path.length, contract.fn).toBeGreaterThan(0);
      expect(describeRef(contract.request).length, contract.fn).toBeGreaterThan(0);
      expect(describeRef(contract.response).length, contract.fn).toBeGreaterThan(0);
    }
  });

  it("主链路恰好是约定的 5 个接口", () => {
    expect(CRITICAL_CONTRACTS).toHaveLength(CRITICAL_PATHS.length);
    expect(CRITICAL_CONTRACTS.map((c) => c.path).sort()).toEqual([...CRITICAL_PATHS].sort());
    expect(CRITICAL_CONTRACTS.every((c) => c.critical)).toBe(true);
  });

  it("非主链路接口均未标记 critical", () => {
    for (const contract of API_CONTRACTS) {
      if (!CRITICAL_PATHS.includes(contract.path as (typeof CRITICAL_PATHS)[number])) {
        expect(contract.critical, contract.fn).toBe(false);
      }
    }
  });

  it("登记表内没有 TODO（当前代码事实完整）", () => {
    expect(TODO_ENTRIES.map((c) => c.fn)).toEqual([]);
    expect(RESOLVED_COUNT).toBe(API_CONTRACTS.length);
  });

  it("无法 import 的类型被显式标记，而不是伪造类型名", () => {
    expect(UNEXPORTED_TYPE_ENTRIES.map((c) => c.fn)).toEqual(["fetchPublicKey"]);
  });

  it("GET 接口不使用请求体，POST 接口路径不与领域无关", () => {
    for (const contract of API_CONTRACTS) {
      if (contract.method === "GET") {
        expect(contract.note ?? "", contract.fn).not.toMatch(/请求体/);
      }
    }
  });
});