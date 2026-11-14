import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import {
  getAdminProducts,
  toggleProductStatus,
  type AdminProductItem,
} from "@/lib/api/admin";
import { formatPrice } from "@/data/products";

/** 商品管理页：列表 + 分页 + 上下架切换。 */
export function ProductsPage() {
  const [page, setPage] = useState(1);
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["admin-products", page],
    queryFn: () => getAdminProducts({ page }),
  });

  const toggleMutation = useMutation({
    mutationFn: (id: number) => toggleProductStatus(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["admin-products"] });
    },
  });

  const columns: Array<DataTableColumn<AdminProductItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    { key: "name", header: "商品名", cell: (row) => row.name },
    { key: "price", header: "价格", cell: (row) => formatPrice(row.price) },
    {
      key: "status",
      header: "状态",
      cell: (row) =>
        row.status === 1 ? (
          <StatusBadge status="上架" type="success" />
        ) : (
          <StatusBadge status="下架" type="danger" />
        ),
    },
    { key: "created_at", header: "创建时间", cell: (row) => row.created_at ?? "-" },
    {
      key: "actions",
      header: "操作",
      cell: (row) => (
        <Button
          variant="outline"
          size="sm"
          disabled={toggleMutation.isPending}
          onClick={() => toggleMutation.mutate(row.id)}
        >
          {row.status === 1 ? "下架" : "上架"}
        </Button>
      ),
    },
  ];

  const lastPage = data?.last_page ?? 1;

  return (
    <AdminPageLayout
      title="商品管理"
      description="商品的上下架与信息维护"
      actions={
        // TODO: 新建商品弹窗（待后端 store 接口联调后接入）
        <Button size="sm">新建商品</Button>
      }
    >
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无商品"
        pagination={
          lastPage > 1 ? (
            <div className="flex items-center justify-between text-sm text-gray-500">
              <span>
                第 {data?.current_page ?? page} / {lastPage} 页，共 {data?.total ?? 0} 条
              </span>
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page <= 1}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                >
                  上一页
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  disabled={page >= lastPage}
                  onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                >
                  下一页
                </Button>
              </div>
            </div>
          ) : null
        }
      />
    </AdminPageLayout>
  );
}
