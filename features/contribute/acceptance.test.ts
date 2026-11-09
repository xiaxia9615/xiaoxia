import { readFileSync } from "node:fs";
import { join } from "node:path";
import { describe, expect, it } from "vitest";
import {
  DEFAULT_PARTICIPATION_TYPE,
  getParticipationStrategy,
  isParticipationType,
  listParticipationStrategies,
} from "./strategies/registry";
import { emptyValuesFor, validateValues } from "./schema";

const ROUTE_FILE = join(process.cwd(), "src/routes/contribute.tsx");
const routeSource = readFileSync(ROUTE_FILE, "utf8");

describe("验收：路由收口", () => {
  it("contribute.tsx 不超过 80 行", () => {
    const lines = routeSource.trimEnd().split("\n").length;
    expect(lines).toBeLessThanOrEqual(80);
  });

  it("路由不含业务提交逻辑（无 mutation / submit / 表单校验）", () => {
    expect(routeSource).not.toMatch(/useMutation|mutateAsync|\.mutate\(/);
    expect(routeSource).not.toMatch(/safeParse|handleSubmit/);
    expect(routeSource).not.toMatch(/submitContribution|toContributeRow/);
  });

  it("路由无内联请求与手写错误提取", () => {
    expect(routeSource).not.toMatch(/\bfetch\(|axios|@\/services\//);
    expect(routeSource).not.toMatch(/instanceof Error\s*&&\s*\w+\.message/);
  });

  it("路由不直接引用参与类型策略实现（新增类型不改路由）", () => {
    expect(routeSource).not.toMatch(/strategies\/(join-plan|registry)|joinPlanStrategy/);
    expect(routeSource).toMatch(/DEFAULT_PARTICIPATION_TYPE/);
  });

  it("路由不含 as DTO 断言", () => {
    expect(routeSource).not.toMatch(/as\s+\w*Dto/);
  });
});

describe("验收：参与类型策略表", () => {
  it("登记表含默认类型，且可通过 id 取到策略", () => {
    const strategies = listParticipationStrategies();
    expect(strategies.length).toBeGreaterThan(0);
    expect(isParticipationType(DEFAULT_PARTICIPATION_TYPE)).toBe(true);
    expect(getParticipationStrategy(DEFAULT_PARTICIPATION_TYPE).id).toBe(
      DEFAULT_PARTICIPATION_TYPE,
    );
  });

  it("未知类型回退默认策略（旧链接仍可用）", () => {
    const fallback = getParticipationStrategy("not-registered-yet");
    expect(fallback.id).toBe(DEFAULT_PARTICIPATION_TYPE);
  });

  it("每个策略自带字段、校验与 payload 映射（页面无需类型分支）", () => {
    for (const strategy of listParticipationStrategies()) {
      expect(strategy.fields.length).toBeGreaterThan(0);
      const empty = emptyValuesFor(strategy);
      expect(validateValues(strategy, empty)).not.toBeNull();
      const required = strategy.fields.find((f) => f.required);
      if (required) {
        const valid = { ...empty, [required.name]: "小夏" };
        expect(validateValues(strategy, valid)).toBeNull();
        const payload = strategy.toPayload(valid);
        expect(payload.name).toBe("小夏");
      }
    }
  });
});
