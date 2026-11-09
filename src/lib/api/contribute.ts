import { ApiError, degrade, reportFallback, toErrorMessage } from "./request";
import { createQmuseRow, listQmuseRows, type Models } from "@/services/appwrite";

/**
 * 开放参与 · 数据访问层
 *
 * 数据源：QMuse 云服务（表 activity_participants），多人共享、随时可读。
 * 本模块是参与功能唯一的数据出口：页面与组件不得直接访问云服务 SDK。
 */

/** 云表行 DTO —— 显式声明字段，作为前后端（云表）契约。 */
export interface ContributeRowDto extends Models.Row {
  name?: string;
  note?: string;
  count?: number;
}

/** 页面模型：与云表行长字段解耦。 */
export interface ContributeItem {
  id: string;
  name: string;
  note: string;
  count: number;
  createdAt: string;
}

/** 提交入参（页面模型 -> 云表行）。 */
export interface ContributeSubmitInput {
  name: string;
  note?: string;
}

/** 参与记录表信息 */
const TABLE_ID = "activity_participants";
const PAGE_SIZE = 20;
const NAME_MAX = 20;
const NOTE_MAX = 40;

/** 提交输入校验：返回字段级错误，合法时返回 null。 */
export function validateContributeInput(input: ContributeSubmitInput): {
  field: "name" | "note";
  message: string;
} | null {
  const name = input.name.trim();
  if (!name) return { field: "name", message: "请填写称呼" };
  if (name.length > NAME_MAX) return { field: "name", message: `称呼不超过 ${NAME_MAX} 个字` };
  const note = input.note?.trim() ?? "";
  if (note.length > NOTE_MAX) return { field: "note", message: `留言不超过 ${NOTE_MAX} 个字` };
  return null;
}

/** 页面模型 -> 云表行字段（显式映射，禁止 as 断言）。 */
export function toContributeRow(input: ContributeSubmitInput): {
  name: string;
  note: string;
  count: number;
} {
  return {
    name: input.name.trim().slice(0, 255),
    note: (input.note ?? "").trim().slice(0, 500),
    count: 1,
  };
}

/** 云表行 -> 页面模型。 */
export function fromContributeResponse(row: ContributeRowDto): ContributeItem {
  return {
    id: typeof row.$id === "string" ? row.$id : "",
    name: typeof row.name === "string" ? row.name : "",
    note: typeof row.note === "string" ? row.note : "",
    count: typeof row.count === "number" ? row.count : 1,
    createdAt: typeof row.$createdAt === "string" ? row.$createdAt : "",
  };
}

/**
 * 参与流列表。
 * 错误分级：属于可展示性数据，失败降级为空列表并留痕，不让整页报错。
 */
export async function listContributeItems(): Promise<{ rows: ContributeItem[]; total: number }> {
  return degrade("参与记录列表", { rows: [], total: 0 }, async () => {
    const page = await listQmuseRows<ContributeRowDto>({
      tableId: TABLE_ID,
      queries: [],
      limit: PAGE_SIZE,
    });
    return { rows: page.rows.map(fromContributeResponse), total: page.total };
  });
}

/**
 * 提交参与记录。
 * 错误分级：写入失败必须显式上抛，由 UI 呈现并保留用户输入；
 * 字段级校验错误以 business 类型抛出，供表单回填到对应字段。
 */
export async function submitContribution(input: ContributeSubmitInput): Promise<ContributeItem> {
  const invalid = validateContributeInput(input);
  if (invalid) {
    throw new ApiError("business", invalid.message);
  }

  try {
    const created = await createQmuseRow<ContributeRowDto>({
      tableId: TABLE_ID,
      data: toContributeRow(input),
    });
    return fromContributeResponse(created);
  } catch (err) {
    // 云服务写入失败：分类为业务错误（不可重试的校验拒绝）或网络错误
    reportFallback("参与提交", err);
    throw new ApiError("network", toErrorMessage(err, "提交失败，请稍后重试"));
  }
}

/** 字段级业务错误 -> 表单字段名。非业务错误返回 null（按系统错误统一提示）。 */
export function mapFieldError(err: unknown): { field: "name" | "note"; message: string } | null {
  if (!(err instanceof ApiError) || err.kind !== "business") return null;
  if (err.message.includes("称呼")) return { field: "name", message: err.message };
  if (err.message.includes("留言")) return { field: "note", message: err.message };
  return null;
}
