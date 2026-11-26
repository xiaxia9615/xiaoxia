import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { createFileRoute } from "@tanstack/react-router";
import { Loader2 } from "lucide-react";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  getAdminLedgerAccounts,
  getAdminLedgerEntries,
  getAdminLedgerStats,
  type LedgerAccountItem,
  type LedgerEntryItem,
} from "@/lib/api/admin";

/** 毫 → 元（账本单位为毫，1 元 = 1000 毫） */
function milliToYuan(milli: number): string {
  return (milli / 1000).toFixed(2);
}

function directionLabel(dir: string): string {
  return dir === "credit" ? "收入" : "支出";
}

function directionType(dir: string): "success" | "danger" | "default" {
  if (dir === "credit") return "success";
  if (dir === "debit") return "danger";
  return "default";
}

function LedgerPage() {
  const [page, setPage] = useState(1);
  const [entryAccountId, setEntryAccountId] = useState<number | null>(null);
  const [entryPage, setEntryPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ["admin-ledger-accounts", page],
    queryFn: () => getAdminLedgerAccounts({ page }),
  });

  const { data: statsData } = useQuery({
    queryKey: ["admin-ledger-stats"],
    queryFn: () => getAdminLedgerStats(),
  });

  const { data: entryData, isLoading: entryLoading } = useQuery({
    queryKey: ["admin-ledger-entries", entryAccountId, entryPage],
    queryFn: () => getAdminLedgerEntries(entryAccountId!, { page: entryPage }),
    enabled: entryAccountId !== null,
  });

  const openEntries = (row: LedgerAccountItem) => {
    setEntryAccountId(row.id);
    setEntryPage(1);
  };

  const closeEntries = () => {
    setEntryAccountId(null);
    setEntryPage(1);
  };

  const columns: Array<DataTableColumn<LedgerAccountItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    { key: "owner_type", header: "归属类型", cell: (row) => row.owner_type },
    { key: "owner_id", header: "归属 ID", cell: (row) => row.owner_id },
    { key: "account_type", header: "账户类型", cell: (row) => row.account_type },
    {
      key: "balance",
      header: "余额",
      cell: (row) => (
        <span
          className={`font-mono ${
            row.balance > 0 ? "text-emerald-400" : row.balance < 0 ? "text-red-400" : "text-slate-400"
          }`}
        >
          ¥{milliToYuan(row.balance)}
        </span>
      ),
    },
    {
      key: "entries_count",
      header: "流水数",
      cell: (row) => (
        <span className="font-mono text-slate-400">{row.entries_count}</span>
      ),
    },
    {
      key: "created_at",
      header: "创建时间",
      cell: (row) => row.created_at ?? "-",
    },
    {
      key: "actions",
      header: "操作",
      cell: (row) => (
        <Button variant="outline" size="sm" onClick={() => openEntries(row)}>
          查看流水
        </Button>
      ),
    },
  ];

  const entryColumns: Array<DataTableColumn<LedgerEntryItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    {
      key: "direction",
      header: "方向",
      cell: (row) => (
        <StatusBadge
          status={directionLabel(row.direction)}
          type={directionType(row.direction)}
        />
      ),
    },
    {
      key: "amount",
      header: "金额",
      cell: (row) => (
        <span
          className={`font-mono ${
            row.direction === "credit" ? "text-emerald-400" : "text-red-400"
          }`}
        >
          {row.direction === "credit" ? "+" : "-"}¥{milliToYuan(row.amount)}
        </span>
      ),
    },
    {
      key: "biz_type",
      header: "业务类型",
      cell: (row) => (
        <code className="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-600">
          {row.biz_type}
        </code>
      ),
    },
    { key: "biz_id", header: "业务 ID", cell: (row) => row.biz_id },
    {
      key: "transaction_id",
      header: "交易 ID",
      cell: (row) => (
        <span className="font-mono text-xs text-gray-500">
          {row.transaction_id.slice(0, 8)}…
        </span>
      ),
    },
    {
      key: "created_at",
      header: "时间",
      cell: (row) => row.created_at ?? "-",
    },
  ];

  return (
    <AdminPageLayout title="账本流水" description="复式记账账户余额与流水记录">
      {statsData && (
        <div className="mb-4 flex gap-6 rounded-lg border border-white/10 bg-black/40 px-6 py-3">
          <div>
            <span className="text-sm text-slate-500">账户数</span>
            <span className="ml-2 font-mono text-lg text-slate-200">
              {statsData.accounts}
            </span>
          </div>
          <div>
            <span className="text-sm text-slate-500">流水数</span>
            <span className="ml-2 font-mono text-lg text-slate-200">
              {statsData.entries}
            </span>
          </div>
          <div>
            <span className="text-sm text-slate-500">总余额</span>
            <span className="ml-2 font-mono text-lg text-emerald-400">
              ¥{milliToYuan(statsData.total_balance)}
            </span>
          </div>
        </div>
      )}

      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无账本账户"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />

      <Dialog open={entryAccountId !== null} onOpenChange={(o) => !o && closeEntries()}>
        <DialogContent className="border-white/10 bg-zinc-950/90 backdrop-blur-xl sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-100">账户流水</DialogTitle>
            <DialogDescription className="text-slate-500">
              账户 #{entryAccountId} 的全部复式记账流水
            </DialogDescription>
          </DialogHeader>
          {entryLoading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin text-slate-400" />
            </div>
          ) : (
            <>
              <DataTable
                columns={entryColumns}
                data={entryData?.list ?? []}
                isLoading={entryLoading}
                emptyText="暂无流水记录"
              />
              {entryData && entryData.last_page > 1 && (
                <Pagination
                  page={entryData.current_page}
                  lastPage={entryData.last_page}
                  total={entryData.total}
                  onChange={setEntryPage}
                />
              )}
            </>
          )}
        </DialogContent>
      </Dialog>
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/ledger")({
  component: LedgerPage,
});
