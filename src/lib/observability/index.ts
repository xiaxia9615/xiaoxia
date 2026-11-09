/**
 * 可观测层入口：错误 + 性能 + 埋点。
 *
 * 在应用启动时调用一次 initObservability()，即可获得：
 * - 全局错误采集（渲染异常、未处理 Promise、资源加载失败）
 * - 首屏性能指标采集（FCP/LCP/CLS/INP/TTFB）
 * - 埋点能力（track / trackPageView）
 *
 * 上报地址通过环境变量 VITE_TELEMETRY_ENDPOINT 配置；未配置时不发网络请求。
 * 需要接到自建采集服务时，用 setTelemetrySink 替换通道，业务代码无需改动。
 */

import { initErrorReporting, reportError } from "./errors";
import { initPerformanceMonitoring } from "./performance";
import { emit, flushTelemetry, setTelemetrySink } from "./transport";
import { TRACK_EVENTS, track, trackPageView } from "./analytics";
import { ApiError, isAuthError, isBusinessError } from "@/lib/api";

export function initObservability(): void {
  if (typeof window === "undefined") return;
  initErrorReporting();
  initPerformanceMonitoring();

  // 页面隐藏时冲刷缓冲：移动端切后台、用户关闭标签页都可能不再回前台
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") flushTelemetry();
  });
  window.addEventListener("pagehide", flushTelemetry);
}

export {
  TRACK_EVENTS,
  emit,
  track,
  trackPageView,
  reportError,
  setTelemetrySink,
  flushTelemetry,
  ApiError,
  isAuthError,
  isBusinessError,
};

export type { TelemetryRecord, TelemetrySink } from "./types";
