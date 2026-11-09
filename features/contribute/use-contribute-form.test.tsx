import { act, renderHook, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import type { ReactNode } from "react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ApiError, type ContributeItem } from "@/lib/api";
import { useContributeForm } from "./use-contribute-form";

const mocks = vi.hoisted(() => ({
  submitContribution: vi.fn(),
}));

vi.mock("@/lib/api", async () => {
  const actual = await vi.importActual<typeof import("@/lib/api")>("@/lib/api");
  return { ...actual, submitContribution: mocks.submitContribution };
});

vi.mock("sonner", () => ({
  toast: { success: vi.fn(), error: vi.fn(), info: vi.fn() },
}));

const created: ContributeItem = {
  id: "r1",
  name: "小夏",
  note: "hi",
  count: 1,
  createdAt: "2026-01-01T00:00:00.000Z",
};

function wrapper({ children }: { children: ReactNode }) {
  const client = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
}

function setup(onSuccess?: (item: ContributeItem) => void) {
  return renderHook(() => useContributeForm({ onSuccess }), { wrapper });
}

describe("useContributeForm", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.localStorage.clear();
  });

  it("称呼为空时给出字段错误且不提交", async () => {
    const { result } = setup();
    await act(async () => {
      await result.current.submit();
    });
    expect(result.current.errors.name).toBe("请填写称呼");
    expect(mocks.submitContribution).not.toHaveBeenCalled();
  });

  it("提交成功后清空表单、写入本地加入标记并回调", async () => {
    mocks.submitContribution.mockResolvedValue(created);
    const onSuccess = vi.fn();
    const { result } = setup(onSuccess);

    act(() => {
      result.current.setField("name", "小夏");
      result.current.setField("note", "hi");
    });
    expect(result.current.dirty).toBe(true);

    await act(async () => {
      await result.current.submit();
    });

    await waitFor(() => expect(onSuccess).toHaveBeenCalledWith(created));
    expect(mocks.submitContribution).toHaveBeenCalledTimes(1);
    expect(result.current.values).toEqual({ name: "", note: "" });
    expect(window.localStorage.getItem("xiaoxia.contribute.joined")).toBe("1");
  });

  it("业务错误回填到对应字段，不重复调用接口", async () => {
    mocks.submitContribution.mockRejectedValueOnce(new ApiError("business", "称呼不能为空"));
    const { result } = setup();

    act(() => result.current.setField("name", "小夏"));
    await act(async () => {
      await result.current.submit();
    });

    await waitFor(() => expect(result.current.errors.name).toBe("称呼不能为空"));
    expect(result.current.values.name).toBe("小夏");
  });

  it("在途提交期间的重复触发被忽略（防重复提交）", async () => {
    let resolveSubmit: (value: ContributeItem) => void = () => {};
    mocks.submitContribution.mockImplementation(
      () =>
        new Promise<ContributeItem>((resolve) => {
          resolveSubmit = resolve;
        }),
    );

    const { result } = setup();
    act(() => result.current.setField("name", "小夏"));

    let first: Promise<ContributeItem | null> = Promise.resolve(null);
    let second: Promise<ContributeItem | null> = Promise.resolve(null);
    await act(async () => {
      first = result.current.submit();
      second = result.current.submit();
    });

    expect(mocks.submitContribution).toHaveBeenCalledTimes(1);
    await expect(second).resolves.toBeNull();

    await act(async () => {
      resolveSubmit(created);
      await first;
    });
    expect(mocks.submitContribution).toHaveBeenCalledTimes(1);
  });

  it("系统错误保留用户输入", async () => {
    mocks.submitContribution.mockRejectedValueOnce(new ApiError("network", "网络失败"));
    const { result } = setup();

    act(() => result.current.setField("name", "小夏"));
    await act(async () => {
      await result.current.submit();
    });

    expect(result.current.values.name).toBe("小夏");
    expect(result.current.errors).toEqual({});
  });

  it("reset 清空字段与错误", async () => {
    const { result } = setup();
    await act(async () => {
      await result.current.submit();
    });
    expect(result.current.errors.name).toBeTruthy();

    act(() => result.current.reset());
    expect(result.current.values).toEqual({ name: "", note: "" });
    expect(result.current.errors).toEqual({});
  });
});
