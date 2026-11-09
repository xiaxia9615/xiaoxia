import { describe, expect, it } from "vitest";
import { getParticipationStrategy } from "./strategies/registry";
import { emptyValuesFor, validateValues } from "./schema";

const strategy = getParticipationStrategy("join-plan");
const empty = emptyValuesFor(strategy);

describe("参与表单校验（策略驱动）", () => {
  it("空称呼被拒绝并给出字段级提示", () => {
    expect(validateValues(strategy, { ...empty, name: "   " })).toEqual({
      field: "name",
      message: "请填写称呼",
    });
  });

  it("称呼为上限长度时通过，超长被拒", () => {
    expect(validateValues(strategy, { ...empty, name: "a".repeat(20) })).toBeNull();
    const tooLong = validateValues(strategy, { ...empty, name: "a".repeat(21) });
    expect(tooLong?.message).toContain("称呼不超过");
  });

  it("留言超长被拒绝并指向 note 字段", () => {
    const result = validateValues(strategy, { name: "小夏", note: "n".repeat(41) });
    expect(result?.field).toBe("note");
  });

  it("合法输入通过校验", () => {
    expect(validateValues(strategy, { name: "小夏", note: "很棒" })).toBeNull();
  });

  it("策略自带 payload 映射（trim 后提交）", () => {
    expect(strategy.toPayload({ name: "  小夏 ", note: " hi " })).toEqual({
      name: "小夏",
      note: "hi",
    });
  });
});
