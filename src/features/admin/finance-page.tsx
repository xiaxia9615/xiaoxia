import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { StatusBadge, type StatusBadgeType } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
  approveWithdrawal,
  getAdminFinanceRecharges,
  getAdminFinanceWithdrawals,
  rejectWithdrawal,
  type AdminRechargeItem,
  type AdminWithdrawalItem,
} from "@/lib/api/admin";
import { formatPrice } from "@/data/products";

/** 提现状态 -> 标签语义。 */
function withdrawalStatusType(status: string): StatusBadgeType {
  if (status === "paid" || status === "approved") return "success";
  if (status === "pending") return "warning";
  if (status === "rejected") return "danger";
  return "default";
}

/** 分页控件（上一页/下一页 + 页码信息）。 */
function Pagination({
  page,
  lastPage,
  total,
  onChange,
}: {
  page: number;
  lastPage: number;
  total: number;
  onChange: (page: number) => void;
}) {
  if (lastPage <= 1) return null;
  return (
    <div className="flex items-center justify-between text-sm text-gray-500">
      <span>
        第 {page} / {lastPage} 页，共 {total} 条
      </span>
      <div className="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          disabled={page <= 1}
          onClick={() => onChange(Math.max(1, page - 1))}
        >
          上一页
        </Button>
        <Button
          variant="outline"
          size="sm"
          disabled={page >= lastPage}
          onClick={() => onChange(Math.min(lastPage, page + 1))}
        >
          下一页
        </Button>
      </div>
    </div>
  );
}

/** 充值流水 Tab。 */
function RechargesTab() {
  const [page, setPage] = useState(1);
  const { data, isLoading } = useQuery({
    queryKey: ["admin-finance-recharges", page],
    queryFn: () => getAdminFinanceRecharges({ page }),
  });

  const columns: Array<DataTableColumn<AdminRechargeItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    { key: "out_trade_no", header: "外部单号", cell: (row) => row.out_trade_no ?? "-" },
    { key: "user_name", header: "用户", cell: (row) => row.user_name ?? "-" },
    { key: "amount", header: "金额", cell: (row) => formatPrice(row.amount) },
    { key: "type", header: "类型", cell: (row) => row.type },
    { key: "created_at", header: "时间", cell: (row) => row.created_at ?? "-" },
  ];

  return (
    <DataTable
      columns={columns}
      data={data?.list ?? []}
      isLoading={isLoading}
      emptyText="暂无充值流水"
      pagination={
        <Pagination
          page={data?.current_page ?? page}
          lastPage={data?.last_page ?? 1}
          total={data?.total ?? 0}
          onChange={setPage}
        />
      }
    />
  );
}

/** 提现审批 Tab：同意打款 / 驳回均需 AlertDialog 二次确认。 */
function WithdrawalsTab() {
  const [page, setPage] = useState(1);
  const queryClient = useQueryClient();
  const [confirming, setConfirming] = useState<
    { kind: "approve" | "reject"; row: AdminWithdrawalItem } | null
  >(null);

  const { data, isLoading } = useQuery({
    queryKey: ["admin-finance-withdrawals", page],
    queryFn: () => getAdminFinanceWithdrawals({ page }),
  });

  const actionMutation = useMutation({
    mutationFn: async (input: { kind: "approve" | "reject"; row: AdminWithdrawalItem }) => {
      if (input.kind === "approve") {
        return approveWithdrawal(input.row.id);
      }
      // 驳回原因：默认文案，后续可扩展为表单输入
      return rejectWithdrawal(input.row.id, "管理员驳回");
    },
    onSuccess: () => {
      setConfirming(null);
      queryClient.invalidateQueries({ queryKey: ["admin-finance-withdrawals"] });
    },
  });

  const columns: Array<DataTableColumn<AdminWithdrawalItem>> = [
    { key: "id", header: "单号", cell: (row) => `#${row.id}` },
    { key: "user_name", header: "用户", cell: (row) => row.user_name ?? "-" },
    { key: "amount", header: "金额", cell: (row) => formatPrice(row.amount) },
    {
      key: "status",
      header: "状态",
      cell: (row) => (
        <StatusBadge status={row.status} type={withdrawalStatusType(row.status)} />
      ),
    },
    {
      key: "actions",
      header: "操作",
      cell: (row) =>
        row.status === "pending" ? (
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setConfirming({ kind: "approve", row })}
            >
              同意打款
            </Button>
            <Button
              variant="outline"
              size="sm"
              className="text-red-600 hover:text-red-700"
              onClick={() => setConfirming({ kind: "reject", row })}
            >
              驳回
            </Button>
          </div>
        ) : (
          <span className="text-xs text-gray-400">已处理</span>
        ),
    },
  ];

  return (
    <>
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无提现申请"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />
      <AlertDialog
        open={confirming !== null}
        onOpenChange={(open) => {
          if (!open) setConfirming(null);
        }}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {confirming?.kind === "approve" ? "确认同意打款" : "确认驳回提现"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {confirming?.kind === "approve"
                ? `确认通过提现单 #${confirming.row.id}（${formatPrice(confirming.row.amount)}）的审批？`
                : `确认驳回提现单 #${confirming?.row.id}（${formatPrice(confirming?.row.amount ?? 0)}）？驳回后冻结款将退回可用余额。`}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={actionMutation.isPending}>取消</AlertDialogCancel>
            <AlertDialogAction
              disabled={actionMutation.isPending}
              onClick={() => {
                if (confirming) actionMutation.mutate(confirming);
              }}
            >
              {actionMutation.isPending ? "处理中…" : "确认"}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}

/** 财务中心页：充值流水 / 提现审批双 Tab。 */
export function FinancePage() {
  return (
    <AdminPageLayout title="财务中心" description="充值流水查询与提现审批">
      <Tabs defaultValue="recharges">
        <TabsList>
          <TabsTrigger value="recharges">充值流水</TabsTrigger>
          <TabsTrigger value="withdrawals">提现审批</TabsTrigger>
        </TabsList>
        <TabsContent value="recharges" className="mt-4">
          <RechargesTab />
        </TabsContent>
        <TabsContent value="withdrawals" className="mt-4">
          <WithdrawalsTab />
        </TabsContent>
      </Tabs>
    </AdminPageLayout>
  );
}
