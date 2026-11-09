import type { ParticipationStrategy } from "./strategies/types";
import { DEFAULT_PARTICIPATION_TYPE, getParticipationStrategy } from "./strategies/registry";

/** 参与表单值（字段由所选参与类型策略声明）。 */
export type ContributeFormValues = Record<string, string>;

export { DEFAULT_PARTICIPATION_TYPE };

/** 按策略生成空的表单初值。 */
export function emptyValuesFor(strategy: ParticipationStrategy): ContributeFormValues {
  return Object.fromEntries(strategy.fields.map((f) => [f.name, ""]));
}

/**
 * 策略驱动的字段校验：返回首个错误，合法时返回 null。
 * 校验规则集中在策略文件内，页面与路由不持有任何规则。
 */
export function validateValues(
  strategy: ParticipationStrategy,
  values: ContributeFormValues,
): { field: string; message: string } | null {
  const result = strategy.schema.safeParse(values);
  if (result.success) return null;
  const issue = result.error.issues[0];
  const field = issue.path[0];
  return {
    field: typeof field === "string" ? field : (strategy.fields[0]?.name ?? "name"),
    message: issue.message,
  };
}

export { getParticipationStrategy };
