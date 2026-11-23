import { useQuery } from "@tanstack/react-query";
import { Users, ShoppingCart, DollarSign, Package, Truck, CheckCircle } from "lucide-react";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { Skeleton } from "@/components/ui/skeleton";
import { getAdminDashboardStats, type AdminDashboardStats } from "@/lib/api/admin";

/** 金额单位转换：毫 → 元（保留 2 位小数）。 */
function formatMilliToYuan(value: number): string {
  return `¥${(value / 1000).toFixed(2)}`;
}

/** KPI 卡片配置。 */
interface KpiCardProps {
  label: string;
  value: string;
  icon: React.ReactNode;
  loading: boolean;
}

function KpiCard({ label, value, icon, loading }: KpiCardProps) {
  return (
    <div className="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
      <div className="flex items-center justify-between">
        <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-slate-500">
          {label}
        </p>
        <div className="text-emerald-600">{icon}</div>
      </div>
      {loading ? (
        <Skeleton className="mt-3 h-8 w-24 bg-zinc-100" />
      ) : (
        <p className="mt-3 font-display text-2xl font-semibold text-zinc-900">
          {value}
        </p>
      )}
    </div>
  );
}

/** Dashboard 页：真实聚合统计数据。 */
export function DashboardPage() {
  const { data, isLoading } = useQuery({
    queryKey: ["admin-dashboard-stats"],
    queryFn: getAdminDashboardStats,
    refetchInterval: 30000,
  });

  const stats: AdminDashboardStats | null = data ?? null;

  return (
    <AdminPageLayout title="控制台" description="系统运营数据总览">
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <KpiCard
          label="总用户数"
          value={stats ? String(stats.total_users) : "0"}
          icon={<Users className="h-4 w-4" />}
          loading={isLoading}
        />
        <KpiCard
          label="今日订单"
          value={stats ? String(stats.today_orders) : "0"}
          icon={<ShoppingCart className="h-4 w-4" />}
          loading={isLoading}
        />
        <KpiCard
          label="今日交易额"
          value={stats ? formatMilliToYuan(stats.today_revenue) : "¥0.00"}
          icon={<DollarSign className="h-4 w-4" />}
          loading={isLoading}
        />
        <KpiCard
          label="商品总数"
          value={stats ? String(stats.total_products) : "0"}
          icon={<Package className="h-4 w-4" />}
          loading={isLoading}
        />
        <KpiCard
          label="待发货"
          value={stats ? String(stats.pending_shipment) : "0"}
          icon={<Truck className="h-4 w-4" />}
          loading={isLoading}
        />
        <KpiCard
          label="已支付订单"
          value={stats ? String(stats.paid_orders) : "0"}
          icon={<CheckCircle className="h-4 w-4" />}
          loading={isLoading}
        />
      </div>

      {/* 数据为空时的提示 */}
      {!isLoading && stats && stats.today_orders === 0 && (
        <div className="mt-6 rounded-xl border border-zinc-200 bg-white p-8 text-center shadow-sm">
          <p className="text-sm text-slate-500">今日暂无订单数据</p>
        </div>
      )}

      {!isLoading && stats && stats.today_revenue === 0 && (
        <div className="mt-4 rounded-xl border border-zinc-200 bg-white p-6 text-center shadow-sm">
          <p className="text-sm text-slate-500">今日交易额：¥0.00</p>
        </div>
      )}

      {/* 图表占位区域 */}
      <div className="mt-6 rounded-xl border border-zinc-200 bg-white p-8 shadow-sm">
        <p className="font-mono text-[11px] font-semibold uppercase tracking-widest text-slate-500">
          趋势图表
        </p>
        <div className="mt-4 flex h-48 items-center justify-center rounded-lg bg-zinc-50">
          <p className="text-sm text-slate-400">暂无图表数据</p>
        </div>
      </div>
    </AdminPageLayout>
  );
}
