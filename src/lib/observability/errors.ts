import { ApiError, isAuthError, isBusinessError } from "@/lib/api";
import { emit } from "./transport";

/**
 * 全局错误采集。
 *
 * 分级原则（与 lib/api 的错误分级一致）：
 * - 业务错误（如库存不足、字段校验失败）属于预期结果，不上报；
 * - 未登录/登录过期由全局登出处理，不上报；
 * - 网络错误、HTTP 错误、渲染异常、未处理的 Promise 拒绝，一律上报。
 */

function shouldSkip(error: unknown): boolean {
  return isBusinessError(error) || isAuthError(error);
}

function describe(error: unknown): { name: string; props: Record<string, unknown> } {
  if (error instanceof ApiError) {
    return {
      name: `ApiError:${error.kind}`,
      props: { message: error.message, status: error.status, code: error.code },
    };
  }
  if (error instanceof Error) {
    return {
      name: error.name || "Error",
      props: { message: error.message, stack: error.stack?.slice(0, 2000) },
    };
  }
  return { name: "UnknownError", props: { value: String(error) } };
}

/** 手动上报错误（供错误边界等显式调用） */
export function reportError(error: unknown, context?: Record<string, unknown>): void {
  if (shouldSkip(error)) return;
  const { name, props } = describe(error);
  emit({ kind: "error", name, props: { ...props, ...context } });
}

let installed = false;

/** 安装全局错误监听（幂等，重复调用安全） */
export function initErrorReporting(): void {
  if (installed || typeof window === "undefined") return;
  installed = true;

  window.addEventListener("error", (event) => {
    // 资源加载失败（img/script）没有 error 对象，单独标记
    const target = event.target;
    if (target instanceof HTMLElement && target !== (window as unknown as HTMLElement)) {
      const tag = target.tagName.toLowerCase();
      if (tag === "img" || tag === "script" || tag === "link") {
        emit({
          kind: "error",
          name: `ResourceError:${tag}`,
          props: {
            src: (target as HTMLImageElement).src?.slice(0, 300),
          },
        });
        return;
      }
    }
    reportError(event.error ?? event.message, { source: "window.onerror" });
  }, true);

  window.addEventListener("unhandledrejection", (event) => {
    reportError(event.reason, { source: "unhandledrejection" });
  });
}
