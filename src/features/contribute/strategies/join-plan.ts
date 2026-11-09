import { z } from "zod";
import type { ContributeSubmitInput } from "@/lib/api";
import type { ParticipationField, ParticipationStrategy } from "./types";

export const NAME_MAX = 20;
export const NOTE_MAX = 40;

const fields: readonly ParticipationField[] = [
  {
    name: "name",
    label: "你的称呼",
    placeholder: "怎么称呼你（必填）",
    required: true,
    maxLength: NAME_MAX,
  },
  {
    name: "note",
    label: "一句话留言",
    placeholder: "为小夏说点什么（选填）",
    required: false,
    maxLength: NOTE_MAX,
  },
];

const schema = z.object({
  name: z.string().trim().min(1, "请填写称呼").max(NAME_MAX, `称呼不超过 ${NAME_MAX} 个字`),
  note: z.string().trim().max(NOTE_MAX, `留言不超过 ${NOTE_MAX} 个字`),
});

/** 加入开放计划：默认参与类型。 */
export const joinPlanStrategy: ParticipationStrategy = {
  id: "join-plan",
  label: "加入开放计划",
  description: "留下你的称呼与一句话，我们会把记录持久化到云，让所有人都能看到。",
  fields,
  schema,
  toPayload(values: Record<string, string>): ContributeSubmitInput {
    return {
      name: (values.name ?? "").trim(),
      note: (values.note ?? "").trim(),
    };
  },
};
