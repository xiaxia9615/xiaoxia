/** 上报记录类型与通道契约 */

export type TelemetryKind = "error" | "metric" | "event";

export interface TelemetryRecord {
  kind: TelemetryKind;
  /** 事件/指标名，错误为错误名或来源 */
  name: string;
  /** 指标数值（metric 使用） */
  value?: number;
  /** 附加信息：错误堆栈、接口路径、业务上下文等（不写入个人信息） */
  props?: Record<string, unknown>;
  /** 采集时间（毫秒时间戳） */
  ts: number;
  /** 会话标识：仅本次标签页有效，不跨会话追踪 */
  session: string;
  /** 路由路径（不含查询串，避免订单号等进入上报） */
  path: string;
}

/** 上报通道：默认走内置 HTTP 批量上报，可替换为自建通道 */
export interface TelemetrySink {
  send: (records: TelemetryRecord[]) => void;
}
