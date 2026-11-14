// 应用入口：样式在 ./styles.css（Tailwind v4 + design token），路由见 ./router.tsx
import React from "react";
import ReactDOM from "react-dom/client";
import { RouterProvider } from "@tanstack/react-router";
import { getRouter } from "./router";
import { initObservability, reportError } from "./lib/observability";
import "./styles.css";
import "./i18n";

// 先装可观测层，才能捕获后续初始化阶段的异常
initObservability();

const router = getRouter();

function reportFatalReactError(
  error: unknown,
  errorInfo: { componentStack?: string },
) {
  reportError(error, {
    source: "react",
    componentStack: errorInfo.componentStack?.slice(0, 2000),
  });
  console.error(error, errorInfo.componentStack);
}

ReactDOM.createRoot(document.getElementById("root")!, {
  onCaughtError: reportFatalReactError,
  onUncaughtError: reportFatalReactError,
}).render(
  <React.StrictMode>
    <RouterProvider router={router} />
  </React.StrictMode>,
);
