import { useMemo, useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Plus, Trash2, Edit2 } from "lucide-react";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";
import { DataTable, type DataTableColumn } from "@/config/components/admin/DataTable";
import { Pagination } from "@/config/components/admin/Pagination";
import { StatusBadge } from "@/config/components/admin/StatusBadge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import {
  getAdminCategories,
  getCategoryTree,
  createCategory,
  updateCategory,
  deleteCategory,
  type CategoryItem,
  type CategoryTreeNode,
} from "@/lib/api/admin";

interface FlatNode { node: CategoryTreeNode; depth: number; }

function flattenTree(nodes: CategoryTreeNode[], depth = 0): FlatNode[] {
  const result: FlatNode[] = [];
  for (const n of nodes) {
    result.push({ node: n, depth });
    if (n.children?.length) result.push(...flattenTree(n.children, depth + 1));
  }
  return result;
}

function collectSelfAndDescendants(flat: FlatNode[], rootId: number): Set<number> {
  const ids = new Set<number>();
  const queue: number[] = [rootId];
  while (queue.length > 0) {
    const id = queue.shift() as number;
    if (ids.has(id)) continue;
    ids.add(id);
    for (const { node } of flat) {
      if (node.parent_id === id) queue.push(node.id);
    }
  }
  return ids;
}

export function CategoriesPage() {
  const [page, setPage] = useState(1);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<CategoryItem | null>(null);
  const [form, setForm] = useState({ name: "", slug: "", parent_id: 0, sort: 0, status: 1 });
  const qc = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["admin-categories", page],
    queryFn: () => getAdminCategories(page),
  });

  const { data: tree } = useQuery({
    queryKey: ["category-tree"],
    queryFn: getCategoryTree,
  });

  const invalidate = () => {
    qc.invalidateQueries({ queryKey: ["admin-categories"] });
    qc.invalidateQueries({ queryKey: ["category-tree"] });
  };

  const createMut = useMutation({
    mutationFn: (payload: typeof form) => createCategory(payload),
    onSuccess: () => { toast.success("创建成功"); invalidate(); setDialogOpen(false); },
    onError: (e: Error) => toast.error(e.message),
  });

  const updateMut = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: typeof form }) => updateCategory(id, payload),
    onSuccess: () => { toast.success("更新成功"); invalidate(); setDialogOpen(false); },
    onError: (e: Error) => toast.error(e.message),
  });

  const deleteMut = useMutation({
    mutationFn: (id: number) => deleteCategory(id),
    onSuccess: () => { toast.success("删除成功"); invalidate(); },
    onError: (e: Error) => toast.error(e.message),
  });

  const flatTree = useMemo(() => (tree ? flattenTree(tree) : []), [tree]);

  const excludeIds: Set<number> = useMemo(
    () => (editing ? collectSelfAndDescendants(flatTree, editing.id) : new Set<number>()),
    [editing, flatTree],
  );

  const parentOptions: FlatNode[] = useMemo(
    () => flatTree.filter(({ node }) => !excludeIds.has(node.id)),
    [flatTree, excludeIds],
  );

  function openCreate() {
    setEditing(null);
    setForm({ name: "", slug: "", parent_id: 0, sort: 0, status: 1 });
    setDialogOpen(true);
  }

  function openEdit(item: CategoryItem) {
    setEditing(item);
    setForm({ name: item.name, slug: item.slug, parent_id: item.parent_id, sort: item.sort, status: item.status });
    setDialogOpen(true);
  }

  function handleSubmit() {
    if (editing) updateMut.mutate({ id: editing.id, payload: form });
    else createMut.mutate(form);
  }

  const columns: Array<DataTableColumn<CategoryItem>> = [
    { key: "id", header: "ID", cell: (row) => row.id },
    { key: "name", header: "名称", cell: (row) => row.name },
    { key: "slug", header: "标识", cell: (row) => row.slug },
    { key: "parent_id", header: "父分类", cell: (row) =>
        row.parent_id === 0 ? "-" : flatTree.find((f) => f.node.id === row.parent_id)?.node.name ?? row.parent_id },
    { key: "sort", header: "排序", cell: (row) => row.sort },
    { key: "status", header: "状态", cell: (row) => (
        <StatusBadge status={row.status === 1 ? "启用" : "禁用"} type={row.status === 1 ? "success" : "default"} />
      ) },
    { key: "actions", header: "操作", cell: (row) => (
        <div className="flex gap-2">
          <Button variant="outline" size="sm" onClick={() => openEdit(row)}><Edit2 className="mr-1 h-3 w-3" />编辑</Button>
          <Button variant="outline" size="sm" onClick={() => { if (confirm("确认删除？")) deleteMut.mutate(row.id); }}>
            <Trash2 className="mr-1 h-3 w-3" />删除
          </Button>
        </div>
      ) },
  ];

  return (
    <AdminPageLayout title="分类管理" description="商品分类的增删改查">
      <div className="mb-4">
        <Button onClick={openCreate}><Plus className="mr-1 h-4 w-4" />新建分类</Button>
      </div>
      <DataTable
        columns={columns}
        data={data?.list ?? []}
        isLoading={isLoading}
        emptyText="暂无分类"
        pagination={
          <Pagination
            page={data?.current_page ?? page}
            lastPage={data?.last_page ?? 1}
            total={data?.total ?? 0}
            onChange={setPage}
          />
        }
      />
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader><DialogTitle>{editing ? "编辑分类" : "新建分类"}</DialogTitle></DialogHeader>
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>名称</Label>
              <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="分类名称" />
            </div>
            <div className="space-y-2">
              <Label>标识</Label>
              <Input value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} placeholder="英文标识" />
            </div>
            <div className="space-y-2">
              <Label>父分类</Label>
              <select className="w-full rounded border border-input px-3 py-2" value={form.parent_id}
                onChange={(e) => setForm({ ...form, parent_id: Number(e.target.value) })}>
                <option value={0}>无（顶级分类）</option>
                {parentOptions.map(({ node, depth }) => (
                  <option key={node.id} value={node.id}>{"  ".repeat(depth)}{node.name}</option>
                ))}
              </select>
            </div>
            <div className="flex gap-4">
              <div className="space-y-2 flex-1">
                <Label>排序</Label>
                <Input type="number" value={form.sort} onChange={(e) => setForm({ ...form, sort: Number(e.target.value) })} />
              </div>
              <div className="space-y-2 flex-1">
                <Label>状态</Label>
                <select className="w-full rounded border border-input px-3 py-2" value={form.status}
                  onChange={(e) => setForm({ ...form, status: Number(e.target.value) })}>
                  <option value={1}>启用</option>
                  <option value={0}>禁用</option>
                </select>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDialogOpen(false)}>取消</Button>
            <Button onClick={handleSubmit} disabled={createMut.isPending || updateMut.isPending}>确定</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AdminPageLayout>
  );
}
