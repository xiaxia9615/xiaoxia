import { useQuery } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";
import { toast } from "sonner";
import { useCartState } from "@/features/cart/cart-provider";
import { formatPrice, getProduct } from "@/data/products";
import { fetchAddresses, fetchCart } from "@/lib/api";
import { TRACK_EVENTS, track } from "@/lib/observability";

/** 购物车条目显示模型：统一服务端购物车与本地购物车两种来源。 */
export interface CartDisplayItem {
  key: string | number;
  id?: number;
  goods_id: number;
  goods_name: string;
  image: string;
  price: number;
  quantity: number;
}

/**
 * 购物车页：本地/服务端双来源统一为显示模型，金额计算与结算跳转。
 * 页面组件只消费本 hook 暴露的状态与动作。
 */
export function useCartPage(loggedIn: boolean) {
  const navigate = useNavigate();
  const { lines, remove } = useCartState();

  const addressesQuery = useQuery({
    queryKey: ["addresses"],
    queryFn: fetchAddresses,
    enabled: loggedIn,
  });

  // 已登录：使用真实购物车（含 cart id）；未登录：本地购物车 + 本地商品目录
  const serverCartQuery = useQuery({
    queryKey: ["cart"],
    queryFn: fetchCart,
    enabled: loggedIn,
  });

  const localEntries = lines
    .map((line) => ({ line, product: getProduct(line.productId) }))
    .filter((entry): entry is { line: (typeof lines)[number]; product: NonNullable<ReturnType<typeof getProduct>> } =>
      Boolean(entry.product),
    );

  const items: CartDisplayItem[] = loggedIn
    ? (serverCartQuery.data ?? []).map((item) => ({
        key: item.id ?? `${item.goods_id}-${item.goods_name}`,
        id: item.id,
        goods_id: Number(item.goods_id ?? 0),
        goods_name: item.goods_name ?? "",
        image: item.image ?? "",
        price: Number(item.price ?? 0),
        quantity: item.quantity ?? 1,
      }))
    : localEntries.map(({ line, product }) => ({
        key: line.productId,
        goods_id: Number(line.productId),
        goods_name: product.name,
        image: product.image,
        price: product.price,
        quantity: line.qty,
      }));

  const subtotal = items.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const shipping = subtotal > 0 && subtotal < 99 ? 10 : 0;
  const total = subtotal + shipping;
  const isEmpty = items.length === 0;

  const removeItem = (item: CartDisplayItem) => {
    if (loggedIn && item.id != null) {
      remove(String(item.goods_id));
      return;
    }
    remove(String(item.goods_id));
  };

  const checkout = async () => {
    if (!loggedIn) {
      toast.info("请先登录后再结算");
      await navigate({ to: "/login", search: { redirect: "/cart" } });
      return;
    }

    const cartIds = (serverCartQuery.data ?? [])
      .map((item) => item.id)
      .filter((id): id is number => typeof id === "number");

    if (cartIds.length === 0) {
      toast.error("购物车中没有可下单的商品");
      return;
    }

    track(TRACK_EVENTS.beginCheckout, { itemCount: cartIds.length });
    await navigate({ to: "/checkout", search: { ids: cartIds.join(",") } });
  };

  return {
    items,
    isEmpty,
    isLoading: loggedIn && serverCartQuery.isLoading,
    isError: loggedIn && serverCartQuery.isError,
    error: serverCartQuery.error,
    refetch: serverCartQuery.refetch,
    subtotal,
    shipping,
    total,
    defaultAddress: (addressesQuery.data ?? []).find((a) => a.is_default) ?? addressesQuery.data?.[0],
    removeItem,
    checkout,
    formatPrice,
  };
}
