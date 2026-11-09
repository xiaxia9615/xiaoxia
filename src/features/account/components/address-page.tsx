import { Link } from "@tanstack/react-router";
import { ArrowLeft, Check, MapPin, Pencil, Plus, Trash2 } from "lucide-react";
import { SiteLayout } from "@/components/site/layout";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import type { Address } from "@/lib/api";
import { toErrorMessage } from "@/lib/api";
import { cn } from "@/lib/utils";
import { AddressDialog } from "../address-dialog";
import { useAddressBook } from "../use-address-book";

/** 收货地址页：业务逻辑在 useAddressBook，组件只负责渲染。 */
export function AddressPage() {
  const {
    addresses,
    isLoading,
    isError,
    error,
    refetch,
    dialogOpen,
    setDialogOpen,
    editing,
    openCreate,
    openEdit,
    defaultMutation,
    deleteMutation,
  } = useAddressBook();

  return (
    <SiteLayout>
      <div className="mx-auto max-w-4xl px-4 pb-10 pt-12 sm:px-6">
        <Link
          to="/"
          className="mb-6 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-clay"
        >
          <ArrowLeft className="size-4" />
          返回首页
        </Link>

        <div className="flex items-center justify-between gap-4">
          <div>
            <h1 className="font-display text-3xl font-bold text-ink">收货地址</h1>
            <p className="mt-1 text-sm text-muted-foreground">
              管理你的收货信息，下单时选择默认地址
            </p>
          </div>
          <Button className="rounded" onClick={openCreate}>
            <Plus className="size-4" />
            <span className="hidden sm:inline">新增地址</span>
          </Button>
        </div>

        <div className="mt-8">
          {isLoading ? (
            <div className="space-y-4">
              <Skeleton className="h-32 w-full rounded-xl" />
              <Skeleton className="h-32 w-full rounded-xl" />
            </div>
          ) : isError ? (
            <div className="flex flex-col items-center rounded-xl border border-dashed border-border bg-card px-6 py-16 text-center">
              <MapPin className="size-8 text-muted-foreground" />
              <h2 className="mt-4 font-display text-lg font-semibold text-ink">
                地址加载失败
              </h2>
              <p className="mt-1 max-w-xs text-sm text-muted-foreground">
                {toErrorMessage(error, "网络异常，请稍后重试")}
              </p>
              <Button className="mt-6 rounded" onClick={() => void refetch()}>
                重新加载
              </Button>
            </div>
          ) : addresses.length > 0 ? (
            <AddressList
              addresses={addresses}
              onEdit={openEdit}
              onSetDefault={(id) => defaultMutation.mutate(id)}
              onDelete={(id) => deleteMutation.mutate(id)}
              pendingDefault={defaultMutation.isPending}
            />
          ) : (
            <div className="flex flex-col items-center rounded-xl border border-dashed border-border bg-card px-6 py-20 text-center">
              <MapPin className="size-8 text-muted-foreground" />
              <h2 className="mt-4 font-display text-lg font-semibold text-ink">
                还没有收货地址
              </h2>
              <p className="mt-1 max-w-xs text-sm text-muted-foreground">
                添加一个地址，下单时就能直接选择。
              </p>
              <Button variant="outline" className="mt-6 rounded" onClick={openCreate}>
                <Plus className="size-4" />
                添加第一个地址
              </Button>
            </div>
          )}
        </div>
      </div>

      <AddressDialog open={dialogOpen} onOpenChange={setDialogOpen} editing={editing} />
    </SiteLayout>
  );
}

function AddressList({
  addresses,
  onEdit,
  onSetDefault,
  onDelete,
  pendingDefault,
}: {
  addresses: Address[];
  onEdit: (address: Address) => void;
  onSetDefault: (id: number) => void;
  onDelete: (id: number) => void;
  pendingDefault: boolean;
}) {
  return (
    <div className="space-y-4">
      {addresses.map((addr) => (
        <div
          key={addr.address_id}
          className="flex flex-col gap-4 rounded-xl border border-border/70 bg-card p-5 shadow-soft sm:flex-row sm:items-center sm:justify-between"
        >
          <div className="flex items-start gap-3">
            <span
              className={cn(
                "grid size-10 shrink-0 place-items-center rounded-xl",
                addr.is_default ? "bg-clay text-clay-foreground" : "bg-surface text-muted-foreground",
              )}
            >
              <MapPin className="size-5" />
            </span>
            <div>
              <div className="flex flex-wrap items-center gap-2">
                <span className="font-semibold text-ink">{addr.name}</span>
                <span className="text-sm text-muted-foreground">{addr.phone}</span>
                {addr.is_default && (
                  <span className="rounded bg-clay/10 px-2 py-0.5 text-xs font-medium text-clay">
                    默认
                  </span>
                )}
              </div>
              <p className="mt-1 text-sm text-muted-foreground">
                {addr.province} {addr.city} {addr.district} {addr.detail}
              </p>
            </div>
          </div>

          <div className="flex shrink-0 items-center gap-2">
            {!addr.is_default && (
              <Button
                variant="ghost"
                size="sm"
                className="rounded"
                disabled={pendingDefault}
                onClick={() => onSetDefault(addr.address_id)}
              >
                <Check className="size-4" />
                设为默认
              </Button>
            )}
            <Button variant="outline" size="sm" className="rounded" onClick={() => onEdit(addr)}>
              <Pencil className="size-4" />
              编辑
            </Button>
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button
                  variant="outline"
                  size="sm"
                  className="rounded text-destructive hover:bg-destructive/10 hover:text-destructive"
                >
                  <Trash2 className="size-4" />
                  删除
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>删除这个地址？</AlertDialogTitle>
                  <AlertDialogDescription>
                    删除后不可恢复，确认要删除 {addr.name} 的收货地址吗？
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>取消</AlertDialogCancel>
                  <AlertDialogAction
                    onClick={() => onDelete(addr.address_id)}
                    className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                  >
                    删除
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </div>
      ))}
    </div>
  );
}
