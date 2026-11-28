import { useState } from "react";
import {
  keepPreviousData,
  useMutation,
  useQuery,
  useQueryClient,
} from "@tanstack/react-query";
import { Loader2, Plus } from "lucide-react";
import { toast } from "sonner";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import {
  getAdminProducts,
  toggleProductStatus,
  type AdminProductItem,
} from "@/lib/api/admin";
import { ArchiveProductDialog } from "./archive-product-dialog";
import { toErrorMessage } from "@/lib/api/request";
import { formatPrice } from "@/data/products";
import { ProductFormModal } from "./product-form-modal";
import { ProductFilterBar } from "./product-filter-bar";

function typeLabel(type: number): string {
  return type === 1 ? "技能" : "商品";
}

/** 商品管理页：列表 + 搜索/筛选 + 新建/编辑弹窗 + 上下架/强制下架。 */
export function ProductsPage() {
  const [page, setPage] = useState(1);
  const [keywordInput, setKeywordInput] = useState("");
  const [keyword, setKeyword] = useState("");
  const [typeFilter, setTypeFilter] = useState("all");
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<AdminProductItem | null>(null);
  const [pendingArchive, setPendingArchive] = useState<AdminProductItem | null>(null);
  /** 正在进行行级操作的商品 id 集合（驱动按钮局部 Loading）。 */
  const [pendingIds, setPendingIds] = useState<ReadonlySet<number>>(new Set());

  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["admin-products", page, keyword, typeFilter],
    queryFn: () =>
      getAdminProducts({
        page,
        keyword: keyword || undefined,
        type: typeFilter === "all" ? undefined : Number(typeFilter),
      }),
    placeholderData: keepPreviousData,
  });

  const addPending = (id: number) =>
    setPendingIds((prev) => new Set(prev).add(id));
  const removePending = (id: number) =>
    setPendingIds((prev) => {
      const next = new Set(prev);
      next.delete(id);
      return next;
    });

  const toggleMutation = useMutation({
    mutationFn: (id: number) => toggleProductStatus(id),
    onMutate: (id) => addPending(id),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin-products"] });
      toast.success("状态已切换", { description: "商品上下架状态已更新。" });
    },
    onError: (err) => {
      toast.error("状态切换失败", { description: toErrorMessage(err) });
    },
    onSettled: (_data, _err, id) => removePending(id),
  });


  const openCreate = () => {
    setEditing(null);
    setModalOpen(true);
  };
  const openEdit = (row: AdminProductItem) => {
    setEditing(row);
    setModalOpen(true);
  };

  const columns: Array<DataTableColumn<AdminProductItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    {
      key: "cover",
      header: "封面",
      cell: (row) =>
        row.cover ? (
          <img
            src={row.cover.startsWith("http") ? row.cover : `https://xiaoxia.ren${row.cover}`}
            alt="封面"
            className="h-10 w-10 rounded object-cover border"
          />
        ) : (
          <span className="text-xs text-muted-foreground">无</span>
        ),
    },
    { key: "name", header: "商品名", cell: (row) => row.name },
    { key: "type", header: "分类", cell: (row) => typeLabel(row.type) },
    {
      key: "nature",
      header: "性质",
      cell: (row) =>
        row.nature === "virtual" ? (
          <StatusBadge status="虚拟" type="warning" />
        ) : (
          <StatusBadge status="实物" type="default" />
        ),
    },
    { key: "price", header: "价格", cell: (row) => formatPrice(row.price) },
    {
      key: "status",
      header: "状态",
      cell: (row) =>
        row.status === 1 ? (
          <StatusBadge status="上架" type="success" />
        ) : row.status === 2 ? (
          <StatusBadge status="强制下架" type="danger" />
        ) : (
          <StatusBadge status="下架" type="danger" />
        ),
    },
    { key: "created_at", header: "创建时间", cell: (row) => row.created_at ?? "-" },
    {
      key: "actions",
      header: "操作",
      cell: (row) => {
        const busy = pendingIds.has(row.id);
        return (
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={busy}
              onClick={() => openEdit(row)}
            >
              编辑
            </Button>
            {row.status !== 2 && (
              <Button
                variant="outline"
                size="sm"
                disabled={busy}
                onClick={() => toggleMutation.mutate(row.id)}
              >
                {busy && toggleMutation.variables === row.id ? (
                  <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                ) : null}
                {row.status === 1 ? "下架" : "上架"}
              </Button>
            )}
            {row.status !== 2 && (
              <Button
                variant="destructive"
                size="sm"
                disabled={busy}
                onClick={() => setPendingArchive(row)}
              >
                强制下架
              </Button>
            )}
          </div>
        );
      },
    },
  ];

  const lastPage = data?.last_page ?? 1;

  return (
    <AdminPageLayout
      title="商品管理"
      description="商品的上下架与信息维护"
      actions={
        <Button size="sm" onClick={openCreate} className="bg-emerald-600 hover:bg-emerald-500">
          <Plus className="mr-1 h-4 w-4" />
          新建商品
        </Button>
      }
    >
      <ProductFilterBar
        keywordInput={keywordInput}
        onKeywordInputChange={setKeywordInput}
        onSearch={() => {
          setPage(1);
          setKeyword(keywordInput.trim());
        }}
        typeFilter={typeFilter}
        onTypeFilterChange={(v) => {
          setTypeFilter(v);
          setPage(1);
        }}
      />

      {isLoading ? (
        <div className="space-y-2" aria-label="加载中">
          {Array.from({ length: 8 }).map((_, i) => (
            <div
              key={i}
              className="h-11 animate-pulse rounded-md border border-white/5 bg-white/5"
              style={{ animationDelay: `${i * 60}ms` }}
            />
          ))}
        </div>
      ) : (
        <DataTable
          columns={columns}
          data={data?.list ?? []}
          isLoading={false}
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
      )}

      <ProductFormModal
        open={modalOpen}
        onOpenChange={setModalOpen}
        editing={editing}
      />
      <ArchiveProductDialog
        product={pendingArchive}
        open={pendingArchive !== null}
        onOpenChange={(open) => {
          if (!open) setPendingArchive(null);
        }}
      />
    </AdminPageLayout>
  );
}
