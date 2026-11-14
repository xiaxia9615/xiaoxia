import type { ReactNode } from "react";
import { cn } from "@/lib/utils";

/** Admin 后台页面骨架：标题区（标题 + 描述 + 右侧操作）与主内容区。 */
interface AdminPageLayoutProps {
  title: string;
  description?: string;
  actions?: ReactNode;
  children: ReactNode;
  /** 终端风暗色主题（仪表盘等极客页面使用） */
  dark?: boolean;
}

export function AdminPageLayout({
  title,
  description,
  actions,
  children,
  dark = false,
}: AdminPageLayoutProps) {
  return (
    <div
      className={cn(
        "mx-auto w-full max-w-6xl px-6 py-8",
        dark && "min-h-screen bg-[#0a0a0a] font-mono text-zinc-300",
      )}
      style={
        dark
          ? {
              backgroundImage:
                "linear-gradient(rgba(16,185,129,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(16,185,129,0.04) 1px, transparent 1px)",
              backgroundSize: "24px 24px",
            }
          : undefined
      }
    >
      <header className="mb-6 flex items-start justify-between gap-4">
        <div className="min-w-0">
          <h1
            className={cn(
              "text-xl font-semibold tracking-tight",
              dark ? "text-emerald-400" : "text-gray-900",
            )}
          >
            {title}
          </h1>
          {description ? (
            <p className={cn("mt-1 text-sm", dark ? "text-zinc-500" : "text-gray-500")}>
              {description}
            </p>
          ) : null}
        </div>
        {actions ? (
          <div className="flex shrink-0 items-center gap-2">{actions}</div>
        ) : null}
      </header>
      <main>{children}</main>
    </div>
  );
}
