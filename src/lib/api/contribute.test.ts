import { describe, expect, it, vi, beforeEach } from "vitest";
import { ApiError } from "./request";
import {
  fromContributeResponse,
  listContributeItems,
  mapFieldError,
  submitContribution,
  toContributeRow,
  validateContributeInput,
  type ContributeRowDto,
} from "./contribute";

/** 构造云表行 DTO：云运行时注入的系统字段在此补齐，避免测试使用 as 断言。 */
function createRow(
  overrides: Partial<ContributeRowDto> & { $id: string },
): ContributeRowDto {
  return {
    $sequence: "1",
    $tableId: "activity_participants",
    $databaseId: "main",
    $createdAt: "2026-01-01T00:00:00.000Z",
    $updatedAt: "2026-01-01T00:00:00.000Z",
    $permissions: [],
    ...overrides,
  };
}

const mocks = vi.hoisted(() => ({
  createQmuseRow: vi.fn(),
  listQmuseRows: vi.fn(),
}));

vi.mock("@/services/appwrite", () => ({
  createQmuseRow: mocks.createQmuseRow,
  listQmuseRows: mocks.listQmuseRows,
}));

describe("contribute 映射函数", () => {
  it("toContributeRow 去除首尾空格并限长", () => {
    const row = toContributeRow({ name: "  小夏  ", note: "  一起成长  " });
    expect(row).toEqual({ name: "小夏", note: "一起成长", count: 1 });
  });

  it("fromContributeResponse 对缺失字段提供兜底（不使用 as 断言）", () => {
    const partial: ContributeRowDto = createRow({ $id: "row-1", $createdAt: "" });
    expect(fromContributeResponse(partial)).toEqual({
      id: "row-1",
      name: "",
      note: "",
      count: 1,
      createdAt: "",
    });
  });

  it("fromContributeResponse 完整映射云表行", () => {
    const row = createRow({
      $id: "row-2",
      name: "阿元",
      note: "支持",
      count: 3,
      $createdAt: "2026-01-01T00:00:00.000Z",
    });
    expect(fromContributeResponse(row)).toEqual({
      id: "row-2",
      name: "阿元",
      note: "支持",
      count: 3,
      createdAt: "2026-01-01T00:00:00.000Z",
    });
  });
});

describe("validateContributeInput", () => {
  it("空称呼返回 name 字段错误", () => {
    expect(validateContributeInput({ name: " " })).toEqual({
      field: "name",
      message: "请填写称呼",
    });
  });

  it("留言超长返回 note 字段错误", () => {
    expect(validateContributeInput({ name: "小夏", note: "n".repeat(41) })).toEqual({
      field: "note",
      message: "留言不超过 40 个字",
    });
  });

  it("合法输入返回 null", () => {
    expect(validateContributeInput({ name: "小夏" })).toBeNull();
  });
});

describe("listContributeItems", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("云服务失败时降级为空列表（不抛错）", async () => {
    mocks.listQmuseRows.mockRejectedValueOnce(new Error("service unavailable"));
    await expect(listContributeItems()).resolves.toEqual({ rows: [], total: 0 });
  });

  it("成功时映射为页面模型", async () => {
    mocks.listQmuseRows.mockResolvedValueOnce({
      total: 1,
      rows: [{ $id: "r1", name: "小夏", note: "hi", count: 2, $createdAt: "2026-01-01T00:00:00.000Z" }],
    });
    const result = await listContributeItems();
    expect(result.total).toBe(1);
    expect(result.rows[0]).toMatchObject({ id: "r1", name: "小夏", count: 2 });
  });
});

describe("submitContribution", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("字段非法时抛 business 错误（供表单回填）", async () => {
    await expect(submitContribution({ name: "" })).rejects.toBeInstanceOf(ApiError);
    await expect(submitContribution({ name: "" })).rejects.toMatchObject({
      kind: "business",
    });
    expect(mocks.createQmuseRow).not.toHaveBeenCalled();
  });

  it("写入成功时返回映射后的参与记录", async () => {
    mocks.createQmuseRow.mockResolvedValueOnce({
      $id: "r9",
      name: "小夏",
      note: "hi",
      count: 1,
      $createdAt: "2026-02-02T00:00:00.000Z",
    });
    const created = await submitContribution({ name: "小夏", note: "hi" });
    expect(created).toMatchObject({ id: "r9", name: "小夏" });
    expect(mocks.createQmuseRow).toHaveBeenCalledTimes(1);
  });

  it("写入失败时显式上抛，不静默吞掉", async () => {
    mocks.createQmuseRow.mockRejectedValue(new Error("write failed"));
    await expect(submitContribution({ name: "小夏" })).rejects.toBeInstanceOf(ApiError);
    await expect(submitContribution({ name: "小夏" })).rejects.toMatchObject({
      kind: "network",
    });
  });
});

describe("mapFieldError", () => {
  it("业务错误映射到字段", () => {
    expect(mapFieldError(new ApiError("business", "称呼不能为空"))).toEqual({
      field: "name",
      message: "称呼不能为空",
    });
  });

  it("非业务错误不映射（按系统错误统一提示）", () => {
    expect(mapFieldError(new ApiError("network", "网络失败"))).toBeNull();
    expect(mapFieldError(new Error("boom"))).toBeNull();
  });
});