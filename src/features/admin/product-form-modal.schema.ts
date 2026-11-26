import { z } from "zod";


/** 分类占位选项（后端 type: 1 技能 / 2 商品；categories 接口当前为空，待后端补全后替换）。 */
export const PRODUCT_TYPES: Array<{ value: string; label: string }> = [
  { value: "1", label: "技能" },
  { value: "2", label: "商品" },
];

export const schema = z
  .object({
    name: z.string().min(1, "商品名称不能为空").max(255, "商品名称不能超过 255 字"),
    priceYuan: z
      .string()
      .min(1, "请输入价格")
      .refine((v) => Number.isFinite(Number(v)) && Number(v) > 0, "价格必须大于 0"),
    type: z.string().min(1, "请选择分类"),
    nature: z.enum(["physical", "virtual"]),
    quotaYuan: z.string(),
    skillId: z.string(),
    cover: z.string(),
    description: z.string(),
  })
  .superRefine((values, ctx) => {
    if (values.nature === "virtual") {
      if (!values.quotaYuan.trim()) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ["quotaYuan"],
          message: "虚拟商品请填写赠送额度",
        });
      } else if (!Number.isFinite(Number(values.quotaYuan)) || Number(values.quotaYuan) <= 0) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ["quotaYuan"],
          message: "赠送额度必须大于 0",
        });
      }
      if (!values.skillId.trim()) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ["skillId"],
          message: "虚拟商品请填写绑定的技能 ID",
        });
      } else if (!Number.isInteger(Number(values.skillId)) || Number(values.skillId) <= 0) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ["skillId"],
          message: "技能 ID 必须为正整数",
        });
      }
    }
  });

export type ProductForm = z.infer<typeof schema>;

export const EMPTY_FORM: ProductForm = {
  name: "",
  priceYuan: "",
  type: "2",
  nature: "physical",
  quotaYuan: "",
  skillId: "",
  cover: "",
  description: "",
};

/** 表单字段 -> 后端字段映射，用于精准回填 422 错误。 */
export const SERVER_FIELD_MAP: Record<string, keyof ProductForm> = {
  name: "name",
  price: "priceYuan",
  type: "type",
  nature: "nature",
  quota: "quotaYuan",
  skill_id: "skillId",
  cover: "cover",
  description: "description",
};
