import { emit } from "./transport";

/**
 * 首屏与交互性能采集（基于 PerformanceObserver，无第三方依赖）。
 *
 * 采集 Core Web Vitals 中的可观测项：
 * - LCP：最大内容绘制
 * - CLS：累计布局偏移
 * - INP 近似值：最长交互延迟（事件处理耗时）
 * - FCP / TTFB：辅助定位首屏瓶颈
 *
 * 指标在 LCP 稳定（首次交互后）或页面隐藏时上报一次。
 */

interface LayoutShiftEntry extends PerformanceEntry {
  value: number;
  hadRecentInput: boolean;
}

interface LargestContentfulPaintEntry extends PerformanceEntry {
  startTime: number;
}

interface FirstInputEntry extends PerformanceEntry {
  processingStart: number;
}

function observe<T extends PerformanceEntry>(
  type: string,
  onEntry: (entry: T) => void,
  buffered = true,
): void {
  if (typeof PerformanceObserver === "undefined") return;
  try {
    const observer = new PerformanceObserver((list) => {
      for (const entry of list.getEntries() as T[]) onEntry(entry);
    });
    observer.observe({ type, buffered } as PerformanceObserverInit);
  } catch {
    // 浏览器不支持该条目类型时静默跳过
  }
}

let cls = 0;
let lcp = 0;
let inp = 0;
let reported = false;
let installed = false;

function report(): void {
  if (reported) return;
  reported = true;

  const nav = performance.getEntriesByType("navigation")[0] as
    | PerformanceNavigationTiming
    | undefined;
  const paint = performance.getEntriesByType("paint");
  const fcp = paint.find((p) => p.name === "first-contentful-paint")?.startTime ?? 0;

  const metrics: Array<[string, number]> = [
    ["fcp", fcp],
    ["lcp", lcp],
    ["cls", cls],
    ["inp", inp],
    ["ttfb", nav ? nav.responseStart - nav.requestStart : 0],
    ["dom_ready", nav ? nav.domContentLoadedEventEnd : 0],
  ];

  for (const [name, value] of metrics) {
    if (value > 0) emit({ kind: "metric", name: `web_vital:${name}`, value: Math.round(value) });
  }
}

/** 安装性能采集（幂等）。返回清理函数，便于测试。 */
export function initPerformanceMonitoring(): () => void {
  if (installed || typeof window === "undefined") return () => undefined;
  installed = true;

  observe<LayoutShiftEntry>("layout-shift", (entry) => {
    if (!entry.hadRecentInput) cls += entry.value;
  });
  observe<LargestContentfulPaintEntry>("largest-contentful-paint", (entry) => {
    lcp = entry.startTime;
  });
  observe<FirstInputEntry>("first-input", (entry) => {
    inp = Math.max(inp, entry.processingStart - entry.startTime);
  });

  const onHidden = () => {
    if (document.visibilityState === "hidden") report();
  };
  const onLoad = () => window.setTimeout(report, 3000);

  document.addEventListener("visibilitychange", onHidden);
  window.addEventListener("load", onLoad);

  return () => {
    document.removeEventListener("visibilitychange", onHidden);
    window.removeEventListener("load", onLoad);
    report();
  };
}

/** 仅用于测试：清空累计值 */
export function resetPerformanceForTest(): void {
  cls = 0;
  lcp = 0;
  inp = 0;
  reported = false;
  installed = false;
}
