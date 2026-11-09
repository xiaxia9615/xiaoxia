import { useCallback, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useNavigate } from "@tanstack/react-router";
import { toast } from "sonner";
import {
  buyAdd,
  fetchAddresses,
  fetchFavorStatus,
  fetchProduct,
  setFavorite,
  toErrorMessage,
} from "@/lib/api";
import { startPay, payErrorMessage } from "@/features/payment";
import { useCartState } from "@/features/cart/cart-provider";
import { useAuth } from "@/features/account/auth-provider";
import { getProduct } from "@/data/products";
import { TRACK_EVENTS, track } from "@/lib/observability";

/** 演示商品（本地字符串 slug id）无法进入服务端交易链路。 */
function isDemoProduct(id: string): boolean {
  return !/^\d+$/.test(id);
}

/**
 * 商品详情页业务逻辑：详情查询、收藏、加购、立即购买。
 * 页面组件只负责渲染与交互状态（图集索引、数量）。
 */
export function useProductDetail(productId: string) {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { add } = useCartState();
  const { user } = useAuth();

  const [qty, setQty] = useState(1);
  const [buying, setBuying] = useState(false);

  const productQuery = useQuery({
    queryKey: ["product", productId],
    queryFn: () => fetchProduct(productId),
  });
  const product = productQuery.data ?? getProduct(productId);

  const favorQuery = useQuery({
    queryKey: ["favor", productId],
    queryFn: () => fetchFavorStatus(Number(productId)),
    enabled: Boolean(user) && Boolean(productId),
  });
  const favorited = favorQuery.data ?? false;

  const favorMut = useMutation({
    mutationFn: (next: boolean) => setFavorite(Number(productId), next),
    onSuccess: (ok) => {
      if (!ok) {
        toast.error("收藏操作失败", { description: "请稍后重试" });
        return;
      }
      toast.success(favorited ? "已取消收藏" : "已收藏");
      void queryClient.invalidateQueries({ queryKey: ["favor", productId] });
      void queryClient.invalidateQueries({ queryKey: ["favorites"] });
    },
  });

  const addressesQuery = useQuery({
    queryKey: ["addresses"],
    queryFn: fetchAddresses,
    enabled: Boolean(user),
  });

  const toggleFavorite = useCallback(async () => {
    if (!user) {
      toast.info("请先登录后再收藏");
      await navigate({ to: "/login", search: { redirect: `/products/${productId}` } });
      return;
    }
    favorMut.mutate(!favorited);
  }, [user, favorited, favorMut, navigate, productId]);

  const addToCart = useCallback(async () => {
    if (!product) return;
    if (user && isDemoProduct(product.id)) {
      toast.error("演示商品暂不支持下单", { description: "请选购在线商品目录中的商品。" });
      return;
    }
    const ok = await add(product.id, qty);
    if (ok) {
      track(TRACK_EVENTS.addToCart, { goodsId: product.id, quantity: qty });
      toast.success("已加入购物车", { description: `${product.name} × ${qty}` });
    }
  }, [product, user, add, qty]);

  /** 立即购买：直购下单（buy_type=goods）后唤起支付。 */
  const buyNow = useCallback(async () => {
    if (!product) return;
    if (!user) {
      toast.info("请先登录后再购买");
      await navigate({ to: "/login" });
      return;
    }
    if (isDemoProduct(product.id)) {
      toast.error("演示商品暂不支持下单", { description: "请选购在线商品目录中的商品。" });
      return;
    }
    const addresses = addressesQuery.data ?? [];
    const defaultAddr = addresses.find((a) => a.is_default) ?? addresses[0];
    if (!defaultAddr) {
      toast.info("请先添加收货地址", {
        description: "购买需要收货地址，先去地址管理添加一个吧。",
      });
      await navigate({ to: "/address" });
      return;
    }

    setBuying(true);
    try {
      const order = await buyAdd({
        buyType: "goods",
        goodsData: [{ goods_id: Number(product.id), stock: qty }],
        addressId: defaultAddr.address_id,
      });
      await startPay(order);
    } catch (err) {
      toast.error("下单未完成", { description: payErrorMessage(err) || toErrorMessage(err) });
    } finally {
      setBuying(false);
    }
  }, [product, user, qty, addressesQuery.data, navigate]);

  return {
    product,
    isFetching: productQuery.isFetching,
    favorited,
    toggleFavorite,
    addToCart,
    buyNow,
    buying,
    qty,
    setQty,
  };
}
