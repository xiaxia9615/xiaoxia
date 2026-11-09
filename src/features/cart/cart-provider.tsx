import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { createContext, useCallback, useContext, useEffect, type ReactNode } from "react";
import { toast } from "sonner";
import { useAuth } from "@/features/account/auth-provider";
import { useCart, type CartApi, type CartLine } from "@/features/cart/use-cart";
import {
  addCartItem,
  deleteCartItem,
  fetchCart,
  toErrorMessage,
  updateCartQuantity,
} from "@/lib/api";

export interface ServerCartItem {
  id?: number;
  goods_id?: number;
  goods_name?: string;
  image?: string;
  price?: number | string;
  quantity?: number;
}

const CartContext = createContext<CartApi | null>(null);

/** 将服务端购物车项归一化为前端 CartLine（productId = goods_id 字符串）。 */
function toLines(items: ServerCartItem[] | undefined): CartLine[] {
  return (items ?? [])
    .filter((item) => item.goods_id != null)
    .map((item) => ({ productId: String(item.goods_id), qty: item.quantity ?? 1 }));
}

export function CartProvider({ children }: { children: ReactNode }) {
  const { user } = useAuth();
  const queryClient = useQueryClient();
  const local = useCart();
  const loggedIn = Boolean(user);

  const serverQuery = useQuery({
    queryKey: ["cart"],
    queryFn: fetchCart,
    enabled: loggedIn,
  });

  // 登录瞬间：本地购物车逐项上传服务器，成功后清空本地，避免数据丢失
  useEffect(() => {
    if (!loggedIn) return;
    let cancelled = false;
    const lines = local.lines;
    if (lines.length === 0) return;
    (async () => {
      let uploaded = 0;
      for (const line of lines) {
        const id = Number(line.productId);
        if (!Number.isFinite(id) || id <= 0) continue; // 本地演示 id（字符串 slug）不进入交易链路
        try {
          await addCartItem(id, line.qty);
          uploaded += 1;
        } catch {
          // 单项失败跳过，保留本地数据供下次登录重试
        }
      }
      if (!cancelled && uploaded > 0) {
        local.clear();
        void queryClient.invalidateQueries({ queryKey: ["cart"] });
        toast.success("已同步本地购物车到你的账号");
      }
    })();
    return () => {
      cancelled = true;
    };
    // 仅在登录态变化时执行一次同步
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [loggedIn]);

  const invalidate = useCallback(() => {
    void queryClient.invalidateQueries({ queryKey: ["cart"] });
  }, [queryClient]);

  const addMutation = useMutation({
    mutationFn: (args: { goodsId: number; qty: number }) =>
      addCartItem(args.goodsId, args.qty),
    onSuccess: invalidate,
  });
  const add = useCallback(
    async (productId: string, qty = 1): Promise<boolean> => {
      const id = Number(productId);
      if (!Number.isFinite(id)) return false;
      try {
        await addMutation.mutateAsync({ goodsId: id, qty });
        return true;
      } catch (err) {
        const msg = toErrorMessage(err, "");
        toast.error("加入购物车失败", { description: msg || "请稍后重试" });
        return false;
      }
    },
    [addMutation],
  );
  const updateMutation = useMutation({
    mutationFn: (args: { id: number; qty: number }) =>
      updateCartQuantity(args.id, args.qty),
    onSuccess: invalidate,
  });
  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteCartItem(id),
    onSuccess: invalidate,
    onError: (err) => {
      const msg = toErrorMessage(err, "");
      toast.error("删除失败", { description: msg || "请稍后重试" });
    },
  });

  // 对外统一接口：未登录用本地，已登录用服务端
  const api: CartApi = loggedIn
    ? {
        lines: toLines(serverQuery.data),
        totalQty: toLines(serverQuery.data).reduce((s, l) => s + l.qty, 0),
        add,
        setQty: (productId, qty) => {
          const item = (serverQuery.data ?? []).find(
            (c) => String(c.goods_id) === productId,
          );
          if (item && item.id != null) updateMutation.mutate({ id: item.id, qty });
        },
        remove: (productId) => {
          const item = (serverQuery.data ?? []).find(
            (c) => String(c.goods_id) === productId,
          );
          if (item && item.id != null) deleteMutation.mutate(item.id);
        },
        clear: () => {
          // 服务端不支持批量清空，逐项删除
          (serverQuery.data ?? []).forEach((item) => {
            if (item.id != null) deleteMutation.mutate(item.id);
          });
        },
      }
    : local;

  return <CartContext.Provider value={api}>{children}</CartContext.Provider>;
}

export function useCartState(): CartApi {
  const ctx = useContext(CartContext);
  if (!ctx) {
    throw new Error("useCartState 必须在 CartProvider 内使用");
  }
  return ctx;
}