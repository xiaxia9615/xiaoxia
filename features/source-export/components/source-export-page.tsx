import { useMemo, useState } from "react";
import { Check, Copy, Download, FileCode2 } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";
import {
  SOURCE_FILES,
  SOURCE_TOTAL_SIZE,
  downloadText,
  toPlainText,
  type SourceFile,
} from "../collect-sources";

function formatSize(chars: number): string {
  if (chars < 1024) return `${chars} B`;
  return `${(chars / 1024).toFixed(1)} KB`;
}

/**
 * 源码导出页（临时工具）：把全部源码拼成文本，支持复制与下载。
 * 导出完成后建议删除本页与对应路由。
 */
export function SourceExportPage() {
  const [active, setActive] = useState<SourceFile | null>(null);
  const [copied, setCopied] = useState(false);

  const plainText = useMemo(() => toPlainText(), []);
  const activeContent = active?.content ?? plainText;
  const activeLabel = active?.path ?? `全部源码（${SOURCE_FILES.length} 个文件）`;

  const copy = async (text: string) => {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 1500);
      toast.success("已复制到剪贴板");
    } catch {
      toast.error("复制失败", { description: "请手动全选下方内容复制" });
    }
  };

  return (
    <SiteLayout>
      <main className="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6">
        <header className="mb-6">
          <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-clay">
            源码导出
          </p>
          <h1 className="mt-2 font-display text-3xl font-semibold text-foreground">
            把前端源码带出去
          </h1>
          <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
            共 {SOURCE_FILES.length} 个文件、约 {formatSize(SOURCE_TOTAL_SIZE)}。点击右侧下载按钮即可存到本地；
            每个文件以 <code className="rounded bg-surface px-1">===== FILE: 路径 =====</code> 开头，
            按其后的路径还原目录结构即可。含 package.json，恢复后执行 npm install 就能跑。
          </p>
          <p className="mt-2 max-w-2xl text-xs text-muted-foreground">
            注意：本页会把源码带进发布产物，导出完成后请在代码中删除本页与
            <code className="mx-1 rounded bg-surface px-1">/source-export</code> 路由。
          </p>
        </header>

        <div className="mb-5 flex flex-wrap gap-3">
          <Button className="rounded" onClick={() => downloadText("xiaoxia-src.txt", plainText)}>
            <Download className="size-4" /> 下载全部源码（.txt）
          </Button>
          <Button
            variant="outline"
            className="rounded"
            onClick={() =>
              downloadText(
                "xiaoxia-src.json",
                JSON.stringify({ files: SOURCE_FILES }, null, 2),
                "application/json",
              )
            }
          >
            <Download className="size-4" /> 下载为 JSON
          </Button>
          <Button variant="outline" className="rounded" onClick={() => void copy(plainText)}>
            {copied ? <Check className="size-4" /> : <Copy className="size-4" />} 复制全部
          </Button>
        </div>

        <div className="grid gap-4 lg:grid-cols-[320px_1fr]">
          <aside className="max-h-[70vh] overflow-y-auto rounded-xl border border-border/70 bg-card p-2">
            {SOURCE_FILES.map((file) => (
              <button
                key={file.path}
                onClick={() => setActive(file)}
                className={`flex w-full items-center gap-2 rounded px-2 py-1.5 text-left text-xs transition-colors hover:bg-surface ${
                  active?.path === file.path ? "bg-surface text-clay" : "text-muted-foreground"
                }`}
              >
                <FileCode2 className="size-3.5 shrink-0" />
                <span className="min-w-0 flex-1 truncate">{file.path}</span>
                <span className="shrink-0 opacity-60">{file.size}</span>
              </button>
            ))}
          </aside>

          <section className="rounded-xl border border-border/70 bg-card">
            <div className="flex items-center justify-between gap-3 border-b border-border/70 px-4 py-3">
              <span className="min-w-0 truncate font-mono text-xs text-muted-foreground">
                {activeLabel}
              </span>
              <div className="flex shrink-0 gap-2">
                <Button
                  size="sm"
                  variant="ghost"
                  className="rounded"
                  onClick={() => void copy(activeContent)}
                >
                  <Copy className="size-3.5" /> 复制
                </Button>
                <Button
                  size="sm"
                  variant="ghost"
                  className="rounded"
                  onClick={() =>
                    downloadText(
                      active ? active.path.split("/").join("-") : "xiaoxia-src.txt",
                      activeContent,
                    )
                  }
                >
                  <Download className="size-3.5" /> 下载
                </Button>
              </div>
            </div>
            <pre className="max-h-[62vh] overflow-auto p-4 font-mono text-xs leading-relaxed text-foreground">
              {activeContent}
            </pre>
          </section>
        </div>
      </main>
    </SiteLayout>
  );
}
