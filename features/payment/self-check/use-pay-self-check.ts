import { useEffect, useMemo, useRef, useState } from "react";
import { detectPayClient, payQuery, paySubmit, toErrorMessage } from "@/lib/api";
import type { CheckStep, LogLine } from "./types";
import { INITIAL_STEPS, isMobileNow, nowStamp } from "./helpers";

/**
 * 支付链路自检状态机：步骤推进、日志、重跑与结论汇总。
 * 页面组件只消费本 hook 暴露的状态与动作。
 */
export function usePaySelfCheck(userLabel: string | null, authLoading: boolean) {
  const [steps, setSteps] = useState<CheckStep[]>(INITIAL_STEPS.map((s) => ({ ...s })));
  const [logs, setLogs] = useState<LogLine[]>([]);
  const [running, setRunning] = useState(false);
  const [done, setDone] = useState(false);
  const logRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    logRef.current?.scrollTo({ top: logRef.current.scrollHeight });
  }, [logs]);

  const log = (text: string, level: LogLine["level"] = "info") => {
    setLogs((prev) => [...prev, { time: nowStamp(), text, level }]);
  };

  const setStep = (key: string, patch: Partial<CheckStep>) => {
    setSteps((prev) => prev.map((s) => (s.key === key ? { ...s, ...patch } : s)));
  };

  const summary = useMemo(() => {
    if (!done) return null;
    const pass = steps.filter((s) => s.status === "pass").length;
    const fail = steps.filter((s) => s.status === "fail").length;
    const warn = steps.filter((s) => s.status === "warn").length;
    if (fail > 0) return { tone: "fail" as const, text: `自检未通过：${fail} 项失败、${warn} 项告警` };
    if (warn > 0) return { tone: "warn" as const, text: `自检完成：${pass} 项通过，${warn} 项告警` };
    return { tone: "pass" as const, text: `自检通过：全部 ${pass} 项正常` };
  }, [done, steps]);

  const runCheck = async () => {
    if (running || authLoading) return;
    setRunning(true);
    setDone(false);
    setSteps(INITIAL_STEPS.map((s) => ({ ...s, status: "idle" })));
    setLogs([]);
    log("pay-self-check v1.0 初始化，开始支付链路自检……");

    // 1. 登录态
    setStep("login", { status: "running" });
    await new Promise((r) => setTimeout(r, 200));
    if (userLabel) {
      setStep("login", { status: "pass", detail: `会话有效 · ${userLabel}`, ms: 200 });
      log(`登录态校验通过，当前用户：${userLabel}`, "ok");
    } else {
      setStep("login", { status: "fail", detail: "未登录，无法执行后续支付探测", ms: 200 });
      log("登录态校验失败：本地无有效会话", "err");
      setRunning(false);
      setDone(true);
      return;
    }

    // 2. 客户端识别
    setStep("client", { status: "running" });
    await new Promise((r) => setTimeout(r, 250));
    const client = detectPayClient();
    const env = client === "app" ? "支付宝小程序/客户端容器" : isMobileNow() ? "移动端浏览器（alipays 协议可用）" : "PC 浏览器（网页收银台）";
    setStep("client", { status: "pass", detail: `${client.toUpperCase()} · ${env}`, ms: 250 });
    log(`支付客户端识别为 ${client}：${env}`, "ok");

    // 3. pay/submit 探测
    setStep("pay", { status: "running" });
    const t0 = performance.now();
    try {
      const pay = await paySubmit({ orderId: 0, payment: "alipay", client });
      const ms = Math.round(performance.now() - t0);
      setStep("pay", {
        status: "pass",
        detail: `接口可达 · 返回 order_sn ${pay.order_sn || "-"}`,
        ms,
      });
      log(`pay/submit 响应 ${ms}ms，支付渠道连通正常`, "ok");
      // 4. pay/query 回查
      setStep("query", { status: "running" });
      const t1 = performance.now();
      try {
        const q = await payQuery(pay.order_id ?? 0);
        const qms = Math.round(performance.now() - t1);
        setStep("query", {
          status: "pass",
          detail: `回查成功 · ${q.status_name || "状态正常"}`,
          ms: qms,
        });
        log(`pay/query 响应 ${qms}ms，订单状态：${q.status_name ?? q.pay_status}`, "ok");
      } catch (e) {
        setStep("query", { status: "fail", detail: toErrorMessage(e, "回查失败"), ms: 0 });
        log("pay/query 回查失败，支付闭环未验证", "err");
      }
    } catch (e) {
      const ms = Math.round(performance.now() - t0);
      const msg = toErrorMessage(e, "接口不可达");
      const isAuthIssue = /登录|token|401/i.test(msg);
      setStep("pay", { status: isAuthIssue ? "warn" : "fail", detail: msg, ms });
      log(`pay/submit 异常（${ms}ms）：${msg}`, isAuthIssue ? "warn" : "err");
      if (isAuthIssue) {
        setStep("query", { status: "warn", detail: "会话可能过期，建议重新登录后再自检" });
        log("检测到令牌可能过期，建议重新登录", "warn");
      } else {
        setStep("query", { status: "idle", detail: "上游失败，跳过回查" });
      }
    }

    log("自检结束。");
    setRunning(false);
    setDone(true);
  };

  return {
    steps,
    logs,
    running,
    done,
    summary,
    logRef,
    runCheck,
  };
}
