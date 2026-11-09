import type { Milestone } from "@/data/contribute";

/** 概览统计块 */
export function StatBlock({
  label,
  value,
  sub,
}: {
  label: string;
  value: string;
  sub: string;
}) {
  return (
    <div className="group rounded border border-border bg-card/60 p-5 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:border-clay/40 hover:shadow-lift">
      <p className="font-mono text-xs uppercase tracking-widest text-muted-foreground">
        {label}
      </p>
      <p className="mt-2 font-display text-3xl font-bold tracking-tight text-ink">
        {value}
      </p>
      <p className="mt-1 font-mono text-xs text-muted-foreground">{sub}</p>
    </div>
  );
}

/** 阶段目标进度条 */
export function ProgressMeter({ label, target, current, unit }: Milestone) {
  const pct = Math.min(100, Math.round((current / target) * 100));
  return (
    <div className="rounded border border-border bg-card/60 p-5 shadow-soft">
      <div className="flex items-center justify-between gap-2">
        <p className="text-sm font-medium text-foreground">{label}</p>
        <p className="font-mono text-xs text-muted-foreground">
          {current.toLocaleString()} / {target.toLocaleString()} {unit}
        </p>
      </div>
      <div className="mt-3 h-2.5 overflow-hidden rounded-full bg-muted">
        <div
          className="h-full rounded-full bg-clay transition-all duration-700"
          style={{ width: `${pct}%` }}
        />
      </div>
      <p className="mt-2 font-mono text-xs text-clay">{pct}% 已完成</p>
    </div>
  );
}
