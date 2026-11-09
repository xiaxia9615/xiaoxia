/** 自检步骤状态 */
export type CheckStatus = "idle" | "running" | "pass" | "warn" | "fail";

export interface CheckStep {
  key: string;
  title: string;
  detail: string;
  status: CheckStatus;
  ms?: number;
}

/** 终端控制台日志行 */
export interface LogLine {
  time: string;
  text: string;
  level: "info" | "ok" | "warn" | "err";
}

export type CheckSummary = {
  tone: "pass" | "warn" | "fail";
  text: string;
};
