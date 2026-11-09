/**
 * 源码导出：把项目源文件以纯文本形式打进构建产物，
 * 便于在预览/发布环境中一次性复制或下载全部代码。
 *
 * 临时工具：导出完成后可删除本目录、对应路由与 router.json 条目。
 */

export interface SourceFile {
  /** 相对项目根的路径，用于展示与还原 */
  path: string;
  content: string;
  /** 字符数，用于展示体积 */
  size: number;
}

// Vite 只接受字面量 glob 模式，这里逐个写死（不能用变量拼接）。
const SRC_FILES = import.meta.glob("/src/**/*.{ts,tsx,css,json}", {
  query: "?raw",
  import: "default",
  eager: true,
});

const E2E_FILES = import.meta.glob("/e2e/**/*.ts", {
  query: "?raw",
  import: "default",
  eager: true,
});

const SCRIPT_FILES = import.meta.glob("/scripts/**/*.mjs", {
  query: "?raw",
  import: "default",
  eager: true,
});

const ROOT_FILES = import.meta.glob("/*.{json,ts}", {
  query: "?raw",
  import: "default",
  eager: true,
});

const EXCLUDED = new Set(["package-lock.json"]);

function collect(): SourceFile[] {
  const files: SourceFile[] = [];
  const sources = [SRC_FILES, E2E_FILES, SCRIPT_FILES, ROOT_FILES];

  for (const matched of sources) {
    for (const [path, content] of Object.entries(matched)) {
      const relative = path.replace(/^\//, "");
      if (EXCLUDED.has(relative)) continue;
      if (relative.endsWith("routeTree.gen.ts")) continue;
      files.push({ path: relative, content, size: content.length });
    }
  }

  return files.sort((a, b) => a.path.localeCompare(b.path));
}

/** 全部可导出源码（按路径排序，稳定可复现） */
export const SOURCE_FILES: SourceFile[] = collect();

export const SOURCE_TOTAL_SIZE = SOURCE_FILES.reduce((sum, f) => sum + f.size, 0);

/** 拼接成单一文本，文件之间用注释头分隔，可据此还原目录结构 */
export function toPlainText(files: SourceFile[] = SOURCE_FILES): string {
  const header = [
    "# 小夏严选 · 前端源码导出",
    `# 文件数：${files.length}，字符数：${files.reduce((s, f) => s + f.size, 0)}`,
    "# 每个文件以 ===== FILE: <路径> ===== 开头，按其后的路径还原目录结构即可。",
    "",
  ].join("\n");

  return (
    header +
    files
      .map(
        (f) =>
          `\n===== FILE: ${f.path} =====\n${f.content}${f.content.endsWith("\n") ? "" : "\n"}`,
      )
      .join("")
  );
}

/** 触发浏览器下载（纯前端，无需服务端） */
export function downloadText(filename: string, text: string, mime = "text/plain"): void {
  const blob = new Blob([text], { type: `${mime};charset=utf-8` });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.append(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}
