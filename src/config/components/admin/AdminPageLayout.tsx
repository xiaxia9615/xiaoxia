import type { ReactNode } from "react";

/** Admin 后台页面骨架：标题区（标题 + 描述 + 右侧操作）与主内容区。 */
interface AdminPageLayoutProps {
  title: string;
  description?: string;
  actions?: ReactNode;
  children: ReactNode;
}

export function AdminPageLayout({
  title,
  description,
  actions,
  children,
}: AdminPageLayoutProps) {
  return (
    <div className="mx-auto w-full max-w-6xl px-6 py-8">
      <header className="mb-6 flex items-start justify-between gap-4">
        <div className="min-w-0">
          <h1 className="text-xl font-semibold tracking-tight text-gray-900">
            {title}
          </h1>
          {description ? (
            <p className="mt-1 text-sm text-gray-500">{description}</p>
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
