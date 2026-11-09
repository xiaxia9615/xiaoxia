/**
 * 开放参与数据流图（内联 SVG，真实连线）。
 * 主路径：访客参与 → QMuse 云入库 → 参与墙展示。
 * 用箭头 marker 表达方向，颜色语义：clay 主路径、ok 完成、coral 异常。
 */

interface FlowNode {
  id: string;
  x: number;
  y: number;
  w: number;
  h: number;
  label: string;
  sub: string;
  tone: "primary" | "ok" | "muted";
}

const NODES: FlowNode[] = [
  { id: "visit", x: 20, y: 96, w: 200, h: 64, label: "访客", sub: "无需登录即可参与", tone: "muted" },
  { id: "submit", x: 300, y: 96, w: 200, h: 64, label: "提交参与", sub: "称呼 · 留言", tone: "primary" },
  { id: "cloud", x: 580, y: 96, w: 200, h: 64, label: "QMuse 云入库", sub: "数据持久化 · 多人共享", tone: "primary" },
  { id: "wall", x: 860, y: 96, w: 200, h: 64, label: "参与墙展示", sub: "实时流 · 统计", tone: "ok" },
];

const EDGES = [
  { from: "visit", to: "submit", label: "填写" },
  { from: "submit", to: "cloud", label: "写入" },
  { from: "cloud", to: "wall", label: "读取" },
];

function nodeCenter(n: FlowNode, key: "left" | "right") {
  return {
    x: key === "left" ? n.x : n.x + n.w,
    y: n.y + n.h / 2,
  };
}

const STROKE = { primary: "var(--clay)", ok: "var(--ok)", muted: "var(--muted-foreground)" };

export function ParticipationFlow() {
  return (
    <div className="overflow-x-auto rounded border border-border bg-card/60 p-5 shadow-soft">
      <svg
        viewBox="0 0 1080 260"
        className="h-auto w-full min-w-[720px]"
        role="img"
        aria-label="开放参与数据流：访客参与后写入云服务，再展示到参与墙"
      >
        <defs>
          {(["primary", "ok", "muted"] as const).map((tone) => (
            <marker
              key={tone}
              id={`arrow-${tone}`}
              viewBox="0 0 10 10"
              refX="9"
              refY="5"
              markerWidth="7"
              markerHeight="7"
              orient="auto-start-reverse"
            >
              <path d="M 0 0 L 10 5 L 0 10 z" fill={STROKE[tone]} />
            </marker>
          ))}
        </defs>

        {/* 连线 */}
        {EDGES.map((e) => {
          const from = NODES.find((n) => n.id === e.from)!;
          const to = NODES.find((n) => n.id === e.to)!;
          const s = nodeCenter(from, "right");
          const t = nodeCenter(to, "left");
          const midX = (s.x + t.x) / 2;
          const tone = e.to === "wall" ? "ok" : "primary";
          return (
            <g key={`${e.from}-${e.to}`}>
              <path
                d={`M ${s.x} ${s.y} C ${midX} ${s.y}, ${midX} ${t.y}, ${t.x} ${t.y}`}
                fill="none"
                stroke={STROKE[tone]}
                strokeWidth={2.5}
                strokeLinecap="round"
                markerEnd={`url(#arrow-${tone})`}
              />
              <g transform={`translate(${midX - 24}, ${(s.y + t.y) / 2 - 26})`}>
                <rect width={48} height={20} rx={4} fill="var(--card)" stroke="var(--border)" strokeWidth={1} />
                <text
                  x={24}
                  y={14}
                  textAnchor="middle"
                  fontSize={12}
                  fill="var(--foreground)"
                  fontFamily="var(--font-mono)"
                >
                  {e.label}
                </text>
              </g>
            </g>
          );
        })}

        {/* 节点 */}
        {NODES.map((n) => {
          const fill =
            n.tone === "primary"
              ? "var(--clay)"
              : n.tone === "ok"
                ? "var(--ok)"
                : "var(--surface)";
          const fg = n.tone === "muted" ? "var(--foreground)" : "var(--clay-foreground)";
          return (
            <g key={n.id}>
              <rect
                x={n.x}
                y={n.y}
                width={n.w}
                height={n.h}
                rx={10}
                fill={fill}
                stroke="var(--border)"
                strokeWidth={1}
              />
              <text
                x={n.x + n.w / 2}
                y={n.y + 26}
                textAnchor="middle"
                fontSize={15}
                fontWeight={700}
                fill={fg}
              >
                {n.label}
              </text>
              <text
                x={n.x + n.w / 2}
                y={n.y + 46}
                textAnchor="middle"
                fontSize={12}
                fill={n.tone === "muted" ? "var(--muted-foreground)" : fg}
                opacity={0.9}
              >
                {n.sub}
              </text>
            </g>
          );
        })}
      </svg>

      {/* 图例 */}
      <div className="mt-4 flex flex-wrap items-center gap-4 font-mono text-xs text-muted-foreground">
        <span className="inline-flex items-center gap-2">
          <span className="size-2.5 rounded-full" style={{ background: "var(--clay)" }} />
          参与写入
        </span>
        <span className="inline-flex items-center gap-2">
          <span className="size-2.5 rounded-full" style={{ background: "var(--ok)" }} />
          完成展示
        </span>
        <span className="inline-flex items-center gap-2">
          <span className="size-2.5 rounded-full" style={{ background: "var(--surface)" }} />
          起点
        </span>
      </div>
    </div>
  );
}
