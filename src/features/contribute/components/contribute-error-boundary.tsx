import { Component, type ErrorInfo, type ReactNode } from "react";
import { Button } from "@/components/ui/button";

/**
 * 参与页错误边界：捕获渲染期异常 -> 统一上报 + 可重试的降级界面。
 * 数据层错误（提交/查询）已在 request 出口分级处理，此处兜底渲染异常。
 */
export class ContributeErrorBoundary extends Component<
  { children: ReactNode },
  { error: Error | null }
> {
  state: { error: Error | null } = { error: null };

  static getDerivedStateFromError(error: Error) {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    window.__MUSE_PREVIEW_ERROR_CAPTURE__?.reportError(error, {
      kind: "react-error-boundary",
      severity: "error",
    });
    if (import.meta.env.DEV) console.error("[contribute] 渲染异常", error, info.componentStack);
  }

  render() {
    if (!this.state.error) return this.props.children;
    return (
      <div className="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
        <h1 className="font-display text-2xl font-bold text-ink">页面加载出了点问题</h1>
        <p className="mt-2 text-sm text-muted-foreground">
          我们已记录该问题。你可以重试，或返回首页稍后再来。
        </p>
        <div className="mt-6 flex gap-3">
          <Button className="rounded" onClick={() => this.setState({ error: null })}>
            重新加载
          </Button>
          <Button variant="outline" className="rounded" asChild>
            <a href="#/">返回首页</a>
          </Button>
        </div>
      </div>
    );
  }
}
