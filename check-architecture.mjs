#!/usr/bin/env node
/**
 * 架构治理检查（CI 门禁）
 *
 * 规则：
 * 1. 路由禁止直接请求：不得使用 fetch/裸 axios/云服务 SDK，一律经 lib/api
 * 2. 路由禁止业务提交逻辑：不得出现 useMutation/mutateAsync/safeParse/handleSubmit
 * 3. 路由与组件禁止直接消费后端 DTO（lib/api 内的 *Dto 类型），且禁止 as DTO 断言
 * 4. 依赖方向矩阵（见 LAYER_RULES）：lib 为底层，禁止反向依赖 features/routes/components
 * 5. 错误只从 lib/api/request.ts 出口判定：业务代码禁止裸写 err instanceof Error && err.message
 * 6. 路由行数预算：已收口路由（ROUTE_LINE_BUDGET）不得超过约定行数
 */
import { readFileSync, readdirSync, statSync } from "node:fs";
import { join, relative } from "node:path";

const SRC = join(process.cwd(), "src");
const failures = [];

/**
 * 路由行数预算：路由只做参数解析、登录守卫、预取、包裹与渲染。
 * 未列入预算的路由仅做体积提示，收口后纳入本表。
 */
const ROUTE_LINE_BUDGET = {
  "src/routes/contribute.tsx": 80,
  "src/routes/address.tsx": 40,
  "src/routes/aftersales.tsx": 40,
  "src/routes/checkout.tsx": 40,
  "src/routes/login.tsx": 40,
  "src/routes/messages.tsx": 40,
  "src/routes/orders_.$orderId.tsx": 40,
  "src/routes/profile.tsx": 40,
  "src/routes/cart.tsx": 40,
  "src/routes/orders.tsx": 40,
  "src/routes/assistant.tsx": 30,
  "src/routes/pay-self-check.tsx": 30,
};
const oversizeRoutes = [];

/** 层级识别（components/ui 为可复用原语，视为共享层，单独归类）。 */
function layerOf(rel) {
  const p = rel.split("\\").join("/");
  if (p.startsWith("src/routes/")) return "routes";
  if (p.startsWith("src/features/")) return "features";
  if (p.startsWith("src/lib/")) return "lib";
  if (p.startsWith("src/services/")) return "services";
  if (p.startsWith("src/components/ui/")) return "ui";
  if (p.startsWith("src/components/")) return "components";
  return "shared"; // data / i18n / types / utils 等
}

/**
 * 依赖方向矩阵：key 层允许 import 的层集合。
 * features 可依赖 lib；lib 不得反向依赖 features。
 */
const LAYER_RULES = {
  routes: ["routes", "features", "components", "ui", "lib", "services", "shared"],
  features: ["features", "components", "ui", "lib", "services", "shared"],
  components: ["components", "ui", "features", "lib", "services", "shared"],
  ui: ["ui", "lib", "shared"],
  lib: ["lib", "services", "shared"],
  services: ["services", "lib", "shared"],
  shared: ["shared", "lib", "services"],
};

function targetLayer(spec) {
  if (spec.startsWith("@/")) return layerOf("src/" + spec.slice(2));
  return null; // 相对路径或三方依赖，跳过
}

function walk(dir) {
  return readdirSync(dir).flatMap((entry) => {
    const full = join(dir, entry);
    if (statSync(full).isDirectory()) return walk(full);
    return /\.(ts|tsx)$/.test(full) && !/\.test\.(ts|tsx)$/.test(full) ? [full] : [];
  });
}

for (const file of walk(SRC)) {
  const rel = relative(process.cwd(), file);
  const layer = layerOf(rel);
  const source = readFileSync(file, "utf8");

  if (layer === "routes") {
    source.split("\n").forEach((line, i) => {
      if (/\bfetch\(|axios|@\/services\//.test(line)) {
        failures.push(`${rel}:${i + 1} 路由禁止直接请求（应经 lib/api）`);
      }
    });
  }

  if (layer !== "lib" && layer !== "services") {
    if (/import\s+(type\s+)?\{[^}]*\b\w+Dto\b[^}]*\}\s+from\s+"@\/lib\/api"/s.test(source)) {
      failures.push(`${rel} 禁止直接消费后端 DTO 类型（请在 lib/api 内完成映射）`);
    }
    if (/as\s+\w*Dto\b/.test(source)) {
      failures.push(`${rel} 禁止使用 as DTO 断言（请在 lib/api 内完成显式映射）`);
    }
  }

  // 路由不得持有业务提交逻辑（全量强制）
  if (layer === "routes") {
    if (/useMutation|mutateAsync|\.mutate\(|safeParse|handleSubmit|submitContribution/.test(source)) {
      failures.push(`${rel} 路由禁止持有业务提交逻辑（应移入 features/*）`);
    }
    // 首屏性能：页面组件必须路由级懒加载，避免全部打进首包
    if (/import\s+\{[^}]+\}\s+from\s+"@\/features\/[^"]*\/components\/[^"]*-page";/.test(source)) {
      failures.push(`${rel} 静态导入页面组件，应改用 lazy() 做路由级代码分割`);
    }
  }

  // 首屏性能：样式表内不得再有字体 @import（会串行阻塞渲染）
  if (rel.endsWith("src/styles.css") || rel.endsWith("src\\styles.css")) {
    if (/@import\s+url\(["']?https:\/\/fonts\./.test(source)) {
      failures.push("src/styles.css 存在字体 @import，应移到入口 HTML 并配合 preconnect");
    }
  }
  const normalized = rel.split("\\").join("/");

  // 路由行数预算
  const budget = ROUTE_LINE_BUDGET[normalized];
  if (budget !== undefined) {
    const lines = source.trimEnd().split("\n").length;
    if (lines > budget) {
      failures.push(`${rel} 超过路由行数预算：${lines} > ${budget} 行`);
    }
  } else if (layer === "routes" && normalized !== "src/routes/__root.tsx") {
    const lines = source.trimEnd().split("\n").length;
    if (lines > 100) oversizeRoutes.push(`${normalized}（${lines} 行）`);
  }

  for (const [, spec] of source.matchAll(/from\s+"(@\/[^"]+)"/g)) {
    const target = targetLayer(spec);
    if (!target) continue;
    if (!LAYER_RULES[layer].includes(target)) {
      failures.push(`${rel} 依赖方向违规：${layer} 不应依赖 ${target}（${spec}）`);
    }
  }

  if (!rel.endsWith("lib/api/request.ts") && !rel.endsWith("lib\\api\\request.ts")) {
    if (/instanceof Error\s*&&\s*\w+\.message/.test(source) && !/toErrorMessage/.test(source)) {
      failures.push(`${rel} 存在手写错误提取，请改用 toErrorMessage（lib/api/request.ts）`);
    }
  }
}

if (failures.length > 0) {
  console.error("架构治理检查未通过：");
  for (const f of failures) console.error(`  - ${f.split("\\").join("/")}`);
  process.exit(1);
}
console.log("架构治理检查通过：路由无直请求、无跨层 DTO 消费、依赖方向正确。");
if (oversizeRoutes.length > 0) {
  console.log(
    `提示：以下路由超过 100 行，尚未纳入行数预算（收口后可加入 ROUTE_LINE_BUDGET）：\n  - ${oversizeRoutes.join("\n  - ")}`,
  );
}
