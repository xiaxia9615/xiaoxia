import type { TelemetryRecord, TelemetrySink } from "./types";

/**
 * 上报通道。
 *
 * 默认行为：
 * - 未配置 VITE_TELEMETRY_ENDPOINT 时不发送网络请求，仅开发环境打印，便于先上线观察；
 * - 配置后按批发送，页面隐藏/卸载时用 sendBeacon 兜底，避免丢数据；
 * - 任何上报失败都不影响业务，静默丢弃。
 *
 * 需要接入自建采集服务时，在启动处调用 setTelemetrySink() 替换通道即可，
 * 业务代码无需改动。
 */

const ENDPOINT = import.meta.env.VITE_TELEMETRY_ENDPOINT;
const BATCH_SIZE = 10;
const FLUSH_INTERVAL = 5000;
const MAX_BUFFER = 50;

let session = "";
let buffer: TelemetryRecord[] = [];
let timer: number | null = null;
let customSink: TelemetrySink | null = null;

function getSession(): string {
  if (session) return session;
  try {
    const key = "xiaoxia.session";
    const existing = window.sessionStorage.getItem(key);
    if (existing) {
      session = existing;
      return session;
    }
    const created = Math.random().toString(36).slice(2, 10);
    window.sessionStorage.setItem(key, created);
    session = created;
  } catch {
    session = "anonymous";
  }
  return session;
}

/** 路由路径（去掉查询串与 hash 前缀，避免把订单号等写入上报） */
function currentPath(): string {
  if (typeof window === "undefined") return "/";
  const hash = window.location.hash.replace(/^#/, "");
  const [path] = hash.split("?");
  return path || "/";
}

function flush(useBeacon = false): void {
  if (buffer.length === 0) return;
  const payload = buffer;
  buffer = [];

  if (customSink) {
    customSink.send(payload);
    return;
  }
  if (!ENDPOINT) {
    if (import.meta.env.DEV) {
      console.debug("[telemetry]", payload);
    }
    return;
  }

  const body = JSON.stringify({ records: payload });
  try {
    if (useBeacon && typeof navigator.sendBeacon === "function") {
      navigator.sendBeacon(ENDPOINT, new Blob([body], { type: "application/json" }));
      return;
    }
    void fetch(ENDPOINT, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body,
      keepalive: true,
    }).catch(() => undefined);
  } catch {
    // 上报失败不影响业务
  }
}

function scheduleFlush(): void {
  if (timer !== null) return;
  timer = window.setTimeout(() => {
    timer = null;
    flush();
  }, FLUSH_INTERVAL);
}

/** 记录一条上报数据 */
export function emit(
  record: Omit<TelemetryRecord, "ts" | "session" | "path">,
): void {
  buffer.push({
    ...record,
    ts: Date.now(),
    session: getSession(),
    path: currentPath(),
  });
  if (buffer.length > MAX_BUFFER) buffer = buffer.slice(-MAX_BUFFER);
  if (buffer.length >= BATCH_SIZE) {
    flush();
    return;
  }
  scheduleFlush();
}

/** 替换上报通道（接入自建采集服务时使用） */
export function setTelemetrySink(sink: TelemetrySink | null): void {
  customSink = sink;
}

/** 页面隐藏或卸载时立即冲刷缓冲，减少丢数据 */
export function flushTelemetry(): void {
  flush(true);
}

/** 仅用于测试：清空内部状态 */
export function resetTelemetryForTest(): void {
  buffer = [];
  session = "";
  customSink = null;
  if (timer !== null) {
    window.clearTimeout(timer);
    timer = null;
  }
}
