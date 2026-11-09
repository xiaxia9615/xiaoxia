import { useEffect, useRef, useState } from "react";

/**
 * 数据流终端：模拟一条主链路请求在五个域之间的流转。
 * 内容来自契约登记表的主链路接口，非真实请求。
 */

interface StreamLine {
  domain: string;
  path: string;
  ms: number;
}

const CHAIN: StreamLine[] = [
  { domain: "乾·auth", path: "POST /login", ms: 42 },
  { domain: "坎·goods", path: "GET /goods/index", ms: 68 },
  { domain: "巽·cart", path: "GET /cart/index", ms: 35 },
  { domain: "兑·order", path: "POST /buy/index", ms: 121 },
  { domain: "离·pay", path: "POST /pay/submit", ms: 187 },
];

export function StreamTerminal() {
  const [count, setCount] = useState(0);
  const timer = useRef<ReturnType<typeof setInterval> | null>(null);

  useEffect(() => {
    timer.current = setInterval(() => {
      setCount((c) => (c >= CHAIN.length + 2 ? 0 : c + 1));
    }, 900);
    return () => {
      if (timer.current) clearInterval(timer.current);
    };
  }, []);

  return (
    <div className="border border-[var(--zhen-line)] bg-[var(--zhen-bg-deep)] p-4 font-mono text-[11px] leading-6">
      <div className="mb-3 flex items-center justify-between border-b border-[var(--zhen-line)] pb-2">
        <span className="text-[var(--zhen-ink-dim)]">zhen / main-chain</span>
        <span className="flex items-center gap-1.5 text-[var(--zhen-gold)]">
          <span className="size-1.5 animate-pulse rounded-full bg-[var(--zhen-gold)]" />
          RUNNING
        </span>
      </div>

      <div className="space-y-0.5">
        {CHAIN.map((line, i) => {
          const visible = i < count;
          return (
            <div
              key={line.path}
              className="zhen-stream-row flex items-center justify-between gap-3"
              style={{
                visibility: visible ? "visible" : "hidden",
                animationDelay: `${i * 40}ms`,
              }}
            >
              <span className="text-[var(--zhen-cinnabar-bright)]">{line.domain}</span>
              <span className="flex-1 truncate text-[var(--zhen-ink)]">{line.path}</span>
              <span className="text-[var(--zhen-ink-dim)]">200 · {line.ms}ms</span>
            </div>
          );
        })}
      </div>

      <div className="mt-3 border-t border-[var(--zhen-line)] pt-2 text-[var(--zhen-ink-dim)]">
        {count > CHAIN.length ? (
          <span className="text-[var(--zhen-gold)]">五行归位 · 交易闭环 ✓</span>
        ) : (
          <span>阵法推演中…</span>
        )}
      </div>
    </div>
  );
}
