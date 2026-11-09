import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { ApiError } from "@/lib/api";
import { reportError } from "./errors";
import { track, trackPageView } from "./analytics";
import {
  emit,
  flushTelemetry,
  resetTelemetryForTest,
  setTelemetrySink,
} from "./transport";
import type { TelemetryRecord } from "./types";

function collect(): { records: TelemetryRecord[]; restore: () => void } {
  const records: TelemetryRecord[] = [];
  setTelemetrySink({ send: (batch) => records.push(...batch) });
  return { records, restore: () => setTelemetrySink(null) };
}

describe("可观测层", () => {
  beforeEach(() => {
    resetTelemetryForTest();
  });

  afterEach(() => {
    resetTelemetryForTest();
    vi.restoreAllMocks();
  });

  it("业务错误与未登录错误不上报（属于预期结果）", () => {
    const { records } = collect();
    reportError(new ApiError("business", "库存不足", 200, 4001));
    reportError(new ApiError("unauthorized", "登录已过期", 401));
    flushTelemetry();
    expect(records).toHaveLength(0);
  });

  it("网络错误与 HTTP 错误上报，并带上状态码", () => {
    const { records } = collect();
    reportError(new ApiError("network", "网络请求失败"));
    reportError(new ApiError("http", "接口请求失败：500", 500));
    flushTelemetry();
    expect(records).toHaveLength(2);
    expect(records[0].kind).toBe("error");
    expect(records[0].name).toBe("ApiError:network");
    expect(records[1].props?.status).toBe(500);
  });

  it("普通异常上报堆栈信息", () => {
    const { records } = collect();
    reportError(new TypeError("foo is not a function"), { source: "test" });
    flushTelemetry();
    expect(records[0].name).toBe("TypeError");
    expect(records[0].props?.source).toBe("test");
  });

  it("埋点携带会话与路径，且路径不含查询串", () => {
    window.location.hash = "#/products/101?from=ad";
    const { records } = collect();
    trackPageView("/products/101");
    flushTelemetry();
    expect(records[0].kind).toBe("event");
    expect(records[0].name).toBe("page_view");
    expect(records[0].path).toBe("/products/101");
    expect(records[0].session).toBeTruthy();
  });

  it("达到批量阈值即发送，缓冲上限生效", () => {
    const { records } = collect();
    for (let i = 0; i < 12; i += 1) track("probe", { i });
    flushTelemetry();
    expect(records).toHaveLength(12);
    expect(records.at(-1)?.props?.i).toBe(11);
  });

  it("单条记录包含时间戳与类型", () => {
    const { records } = collect();
    emit({ kind: "metric", name: "web_vital:lcp", value: 1234 });
    flushTelemetry();
    expect(records[0].kind).toBe("metric");
    expect(records[0].value).toBe(1234);
    expect(records[0].ts).toBeGreaterThan(0);
  });
});
