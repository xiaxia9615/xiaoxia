import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { cn } from "@/lib/utils";

/* ================= 类型定义 ================= */

interface KpiCard {
  id: string;
  title: string;
  value: string;
  delta: string;
  up: boolean;
}

interface ServiceCheck {
  name: string;
  latency: string;
  ok: boolean;
}

interface LogEntry {
  time: string;
  ip: string;
  method: string;
  path: string;
  status: number;
  latency: string;
}

/* ================= 静态演示数据（待接真实接口） ================= */

const KPIS: KpiCard[] = [
  { id: "01", title: "> USER_COUNT", value: "12,482", delta: "+12.5%", up: true },
  { id: "02", title: "> API_CALLS", value: "1,204,566", delta: "+8.2%", up: true },
  { id: "03", title: "> ACTIVE_SKILLS", value: "86", delta: "+3.1%", up: true },
  { id: "04", title: "> TOTAL_REVENUE", value: "¥482,910", delta: "-1.4%", up: false },
];

const CHART_DATA: number[] = [
  12, 28, 22, 45, 38, 52, 41, 60, 55, 72, 66, 88, 79, 95, 84, 102, 91, 110,
  98, 124, 112, 130, 118, 142,
];

const SERVICES: ServiceCheck[] = [
  { name: "DB_CONNECTION", latency: "12ms", ok: true },
  { name: "REDIS_CACHE", latency: "2ms", ok: true },
  { name: "MYSQL_REPLICA", latency: "18ms", ok: true },
  { name: "QUEUE_WORKER", latency: "TIMEOUT", ok: false },
  { name: "OPENAI_GATEWAY", latency: "240ms", ok: true },
  { name: "PAY_CHANNEL", latency: "95ms", ok: true },
  { name: "STORAGE_S3", latency: "31ms", ok: true },
];

const LOGS: LogEntry[] = [
  { time: "10:45:12", ip: "192.168.1.1", method: "GET", path: "/api/v1/chat", status: 200, latency: "120ms" },
  { time: "10:44:58", ip: "10.0.0.42", method: "POST", path: "/api/v1/orders", status: 200, latency: "88ms" },
  { time: "10:44:31", ip: "172.16.0.8", method: "GET", path: "/api/v1/skills", status: 200, latency: "45ms" },
  { time: "10:43:59", ip: "192.168.1.1", method: "POST", path: "/api/v1/pay/submit", status: 500, latency: "3.2s" },
  { time: "10:43:20", ip: "10.0.0.17", method: "GET", path: "/api/v1/users", status: 200, latency: "64ms" },
  { time: "10:42:47", ip: "172.16.0.8", method: "GET", path: "/api/v1/finance/recharges", status: 200, latency: "51ms" },
];

/* ================= 终端折线图（纯 SVG） ================= */

const CHART_W = 640;
const CHART_H = 220;
const PAD = 28;

function buildPoints(data: number[]): Array<{ x: number; y: number }> {
  const max = Math.max(...data);
  return data.map((v, i) => ({
    x: PAD + (i * (CHART_W - PAD * 2)) / (data.length - 1),
    y: CHART_H - PAD - (v / max) * (CHART_H - PAD * 2),
  }));
}

function TerminalChart() {
  const points = buildPoints(CHART_DATA);
  const linePath = points
    .map((p, i) => `${i === 0 ? "M" : "L"}${p.x.toFixed(1)},${p.y.toFixed(1)}`)
    .join(" ");
  const areaPath = `${linePath} L${points[points.length - 1].x.toFixed(1)},${CHART_H - PAD} L${points[0].x.toFixed(1)},${CHART_H - PAD} Z`;
  const hLines = [0.25, 0.5, 0.75].map(
    (r) => CHART_H - PAD - r * (CHART_H - PAD * 2),
  );
  const vLines = [1 / 3, 2 / 3].map(
    (r) => PAD + r * (CHART_W - PAD * 2),
  );

  return (
    <div className="relative rounded border border-zinc-800 bg-[#121212] p-4 transition-colors hover:border-emerald-500/50">
      <span className="absolute right-3 top-2 text-[10px] text-zinc-600">{"// METRICS.24H"}</span>
      <div className="mb-3 flex items-center justify-between">
        <span className="text-xs text-zinc-500">{"$ tail -f /var/log/api_calls.metrics"}</span>
        <span className="text-xs text-emerald-500">LIVE</span>
      </div>
      <svg
        viewBox={`0 0 ${CHART_W} ${CHART_H}`}
        className="block w-full"
        role="img"
        aria-label="API calls trend"
      >
        {/* 网格 */}
        {hLines.map((y) => (
          <line key={`h${y}`} x1={PAD} y1={y} x2={CHART_W - PAD} y2={y} stroke="#27272a" strokeWidth="1" />
        ))}
        {vLines.map((x) => (
          <line key={`v${x}`} x1={x} y1={PAD} x2={x} y2={CHART_H - PAD} stroke="#1c1c1e" strokeWidth="1" />
        ))}
        {/* 坐标轴 */}
        <line x1={PAD} y1={PAD} x2={PAD} y2={CHART_H - PAD} stroke="#3f3f46" strokeWidth="1" />
        <line x1={PAD} y1={CHART_H - PAD} x2={CHART_W - PAD} y2={CHART_H - PAD} stroke="#3f3f46" strokeWidth="1" />
        {/* 面积填充 */}
        <path d={areaPath} fill="rgba(16,185,129,0.07)" />
        {/* 尖锐折线 + 发光 */}
        <path
          d={linePath}
          fill="none"
          stroke="#10b981"
          strokeWidth="1.8"
          strokeLinejoin="miter"
          style={{ filter: "drop-shadow(0 0 6px rgba(16,185,129,0.65))" }}
        />
        {/* 数据点 */}
        {points.map((p, i) => (
          <rect key={i} x={p.x - 1.5} y={p.y - 1.5} width="3" height="3" fill="#34d399" />
        ))}
        {/* 末点高亮 */}
        <circle
          cx={points[points.length - 1].x}
          cy={points[points.length - 1].y}
          r="3.5"
          fill="#10b981"
          style={{ filter: "drop-shadow(0 0 8px rgba(16,185,129,0.9))" }}
        />
      </svg>
      <div className="mt-2 flex justify-between text-[10px] text-zinc-600">
        <span>00:00</span>
        <span>06:00</span>
        <span>12:00</span>
        <span>18:00</span>
        <span>24:00</span>
      </div>
    </div>
  );
}

/* ================= 服务监控台 ================= */

function ServiceMonitor() {
  return (
    <div className="relative rounded border border-zinc-800 bg-[#121212] p-4 transition-colors hover:border-emerald-500/50">
      <span className="absolute right-3 top-2 text-[10px] text-zinc-600">{"// HEALTHCHECK"}</span>
      <div className="mb-3 flex items-center justify-between">
        <span className="text-xs text-zinc-500">{"$ systemctl status --all"}</span>
        <span className="animate-pulse text-emerald-500">●</span>
      </div>
      <div className="space-y-2 text-xs leading-relaxed">
        {SERVICES.map((s) => (
          <div key={s.name} className="flex items-center justify-between">
            <span className="flex items-center gap-2">
              <span className={s.ok ? "text-emerald-500" : "text-red-500"}>■</span>
              <span className={s.ok ? "text-emerald-400" : "text-red-400"}>
                [{s.ok ? "OK" : "FAIL"}]
              </span>
              <span className="text-zinc-400">{s.name}</span>
            </span>
            <span className={s.ok ? "text-zinc-500" : "text-red-500"}>{s.latency}</span>
          </div>
        ))}
      </div>
      <div className="mt-4 border-t border-zinc-800 pt-3 text-[11px] text-zinc-600">
        <span className="text-emerald-500">root@prod-01</span>
        <span>:~$ </span>
        <span className="animate-pulse text-emerald-400">_</span>
      </div>
    </div>
  );
}

/* ================= 页面主体 ================= */

function statusBadgeClass(status: number): string {
  if (status >= 500) return "bg-red-500 text-black";
  if (status >= 400) return "bg-amber-500 text-black";
  return "bg-emerald-500 text-black";
}

export function DashboardPage() {
  const columns: Array<DataTableColumn<LogEntry>> = [
    {
      key: "time",
      header: "TIME",
      cell: (row) => <span className="text-zinc-500">[{row.time}]</span>,
    },
    { key: "ip", header: "CLIENT", cell: (row) => row.ip },
    {
      key: "method",
      header: "METHOD",
      cell: (row) => <span className="text-cyan-400">{row.method}</span>,
    },
    { key: "path", header: "ENDPOINT", cell: (row) => row.path },
    {
      key: "status",
      header: "CODE",
      cell: (row) => (
        <span
          className={cn(
            "inline-block rounded-sm px-1.5 py-0.5 text-[11px] font-bold",
            statusBadgeClass(row.status),
          )}
        >
          {row.status}
        </span>
      ),
    },
    { key: "latency", header: "RTT", cell: (row) => <span className="text-zinc-500">{row.latency}</span> },
  ];

  return (
    <AdminPageLayout
      dark
      title="> SYSTEM_DASHBOARD"
      description="last login: 2026-11-14 20:24:05 from tty0 // uptime 42d 13h"
    >
      {/* KPI 卡片 */}
      <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {KPIS.map((kpi) => (
          <div
            key={kpi.id}
            className="relative rounded border border-zinc-800 bg-[#121212] p-4 transition-colors hover:border-emerald-500/50"
          >
            <span className="absolute right-2 top-1.5 text-[9px] text-zinc-700">
              {"// SYS." + kpi.id}
            </span>
            <div className="text-xs text-zinc-500">{kpi.title}</div>
            <div className="mt-2 flex items-baseline text-2xl font-bold text-emerald-400">
              {kpi.value}
              <span className="ml-1 animate-pulse text-emerald-500">_</span>
            </div>
            <div
              className={cn(
                "mt-2 text-[11px]",
                kpi.up ? "text-emerald-500" : "text-red-500",
              )}
            >
              [ {kpi.delta} ]
            </div>
          </div>
        ))}
      </div>

      {/* 图表区 */}
      <div className="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <TerminalChart />
        </div>
        <ServiceMonitor />
      </div>

      {/* 最近活动日志 */}
      <div className="relative">
        <div className="mb-3 flex items-center justify-between">
          <span className="text-xs text-zinc-500">{"$ journalctl -u api-gateway --since \"10 min ago\""}</span>
          <span className="text-[10px] text-zinc-600">{"// RECENT_ACTIVITY"}</span>
        </div>
        <DataTable
          dark
          columns={columns}
          data={LOGS}
          emptyText="-- no entries --"
        />
      </div>
    </AdminPageLayout>
  );
}
