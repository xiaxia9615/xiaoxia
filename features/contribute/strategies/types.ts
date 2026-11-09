import { z } from "zod";
import type { ContributeSubmitInput } from "@/lib/api";

/** 参与类型策略中声明的表单字段。 */
export interface ParticipationField {
  name: "name" | "note";
  label: string;
  placeholder: string;
  required: boolean;
  maxLength: number;
}

/**
 * 参与类型策略：一种参与类型一份实现。
 * 新增类型 = 新增一个策略文件 + 在 registry 登记一行，路由与页面无需改动。
 */
export interface ParticipationStrategy {
  readonly id: string;
  /** 类型展示名（弹窗标题与按钮文案） */
  readonly label: string;
  readonly description: string;
  readonly fields: readonly ParticipationField[];
  /** 表单校验规则（字段级提示文案） */
  readonly schema: z.ZodType<Record<string, string>>;
  /** 表单值 -> 提交入参（显式映射，禁止 as 断言） */
  toPayload(values: Record<string, string>): ContributeSubmitInput;
}
