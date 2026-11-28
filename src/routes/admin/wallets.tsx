import { useState } from "react";
import { formatMilliAmount } from "@/lib/utils";
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
  getAdminWallets,
  getWalletTransactions,
  type AdminWalletItem,
  type WalletTxItem,
} from "@/lib/api/admin";

function txTypeLabel(type: string): string {
  const map: Record<string, string> = {
    recharge: "充值",
    deduct: "扣款",
    freeze: "冻结",
    refund: "退款",
  };
  return map[type] ?? type;
}

function txTypeBadge(type: string): "success" | "warning" | "danger" | "default" {
  if (type === "recharge" || type === "refund") return "success";
  if (type === "freeze") return "warning";
  if (type === "deduct") return "danger";
  return "default";
}

function WalletsPage() {
  const [page, setPage] = useState(1);
  const [txWalletId, setTxWalletId] = useState<number | null>(null);
  const [txPage, setTxPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ["admin-wallets", page],
    queryFn: () => getAdminWallets({ page }),
  });

  const { data: txData, isLoading: txLoading } = useQuery({
    queryKey: ["admin-wallet-txs", txWalletId, txPage],
    queryFn: () => getWalletTransactions(txWalletId!, { page: txPage }),
    enabled: txWalletId !== null,
  });

  const openTx = (row: AdminWalletItem) => {
    setTxWalletId(row.id);
    setTxPage(1);
  };

  const closeTx = () => {
    setTxWalletId(null);
    setTxPage(1);
  };

  const columns: Array<DataTableColumn<AdminWalletItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    { key: "user_id", header: "用户 ID", cell: (row) => row.user_id },
    {
      key: "user_email",
      header: "用户邮箱",
      cell: (row) => row.user_email ?? "-",
    },
    {
      key: "balance",
      header: "余额",
      cell: (row) => (
        <span className="font-mono text-emerald-400">{formatMilliAmount(row.balance)}</span>
      ),
    },
    {
      key: "frozen_balance",
      header: "冻结",
      cell: (row) => (
        <span className="font-mono text-amber-400">{formatMilliAmount(row.frozen_balance)}</span>
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
        <Button variant="outline" size="sm" onClick={() => openTx(row)}>
          交易记录
        </Button>
      ),
    },
  ];

  const txColumns: Array<DataTableColumn<WalletTxItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    {
      key: "type",
      header: "类型",
      cell: (row) => (
        <StatusBadge status={txTypeLabel(row.type)} type={txTypeBadge(row.type)} />
      ),
    },
    {
      key: "amount",
      header: "金额",
      cell: (row) => (
        <span
          className={`font-mono ${
            row.type === "recharge" || row.type === "refund"
              ? "text-emerald-400"
              : "text-red-400"
          }`}
        >
          {row.type === "recharge" || row.type === "refund" ? "+" : "-"}¥
          {formatMilliAmount(row.amount).replace("¥", "")}
        </span>
      ),
    },
    {
      key: "description",
      header: "描述",
      cell: (row) => row.description ?? "-",
    },
    {
      key: "out_trade_no",
      header: "交易号",
      cell: (row) =>
        row.out_trade_no ? (
          <code className="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-gray-600">
            {row.out_trade_no}
          </code>
        ) : (
          "-"
        ),
    },
    {
      key: "created_at",
      header: "时间",
      cell: (row) => row.created_at ?? "-",
    },
  ];

  return (
    <AdminPageLayout title="钱包管理" description="用户钱包余额与交易记录">
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无钱包记录"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />

      <Dialog open={txWalletId !== null} onOpenChange={(o) => !o && closeTx()}>
        <DialogContent className="border-white/10 bg-zinc-950/90 backdrop-blur-xl sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-100">交易记录</DialogTitle>
            <DialogDescription className="text-slate-500">
              {txData && (
                <span>
                  钱包 #{txData.wallet.id} — 余额 {formatMilliAmount(txData.wallet.balance)}
                  / 冻结 {formatMilliAmount(txData.wallet.frozen_balance)}
                </span>
              )}
            </DialogDescription>
          </DialogHeader>
          {txLoading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin text-slate-400" />
            </div>
          ) : (
            <>
              <DataTable
                columns={txColumns}
                data={txData?.list ?? []}
                isLoading={txLoading}
                emptyText="暂无交易记录"
              />
              {txData && txData.last_page > 1 && (
                <Pagination
                  page={txData.current_page}
                  lastPage={txData.last_page}
                  total={txData.total}
                  onChange={setTxPage}
                />
              )}
            </>
          )}
        </DialogContent>
      </Dialog>
    </AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/wallets")({
  component: WalletsPage,
});
