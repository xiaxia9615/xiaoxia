import { Link } from "@tanstack/react-router";
import { ShoppingBag, Star } from "lucide-react";
import { toast } from "sonner";
import { useCartState } from "@/features/cart/cart-provider";
import { Button } from "@/components/ui/button";
import { formatPrice, type Product } from "@/data/products";
import { AppImage } from "@/components/system/app-image";

export function ProductCard({ product }: { product: Product }) {
  const { add } = useCartState();

  const handleAdd = async () => {
    const ok = await add(product.id);
    if (ok) {
      toast.success("已加入购物车", { description: product.name });
    }
  };

  return (
    <article className="group flex flex-col overflow-hidden rounded-xl border border-border/70 bg-card transition-colors hover:border-clay/40">
      <Link
        to="/products/$productId"
        params={{ productId: product.id }}
        className="relative block overflow-hidden bg-surface"
      >
        <AppImage
          src={product.cover ? (product.cover.startsWith("http") ? product.cover : `https://xiaoxia.ren${product.cover}`) : product.image}
          alt={product.name}
          className="aspect-square w-full object-cover transition-transform duration-500 group-hover:scale-105"
        />
        {product.featured && (
          <span className="absolute left-3 top-3 rounded bg-clay px-2 py-0.5 font-mono text-xs font-semibold text-clay-foreground">
            精选
          </span>
        )}
      </Link>

      <div className="flex flex-1 flex-col gap-2 p-4 sm:p-5">
        <div className="flex items-center justify-between">
          <span className="font-mono text-xs text-muted-foreground">
            {product.category === "drink"
              ? "茶饮器物"
              : product.category === "home"
                ? "家居织物"
                : "氛围陈设"}
          </span>
          <span className="flex items-center gap-1 font-mono text-xs text-muted-foreground">
            <Star className="size-3.5 fill-amber-400 text-amber-400" />
            {product.rating.toFixed(1)}
          </span>
        </div>

        <Link
          to="/products/$productId"
          params={{ productId: product.id }}
          className="font-display text-base font-semibold leading-snug text-ink transition-colors hover:text-clay"
        >
          {product.name}
        </Link>
        <p className="line-clamp-1 text-xs text-muted-foreground">{product.description}</p>

        <div className="mt-auto flex items-center justify-between pt-2">
          <div className="flex items-baseline gap-1.5">
            <span className="font-display text-lg font-bold text-clay">
              {formatPrice(product.price)}
            </span>
            <span className="font-mono text-xs text-muted-foreground line-through">
              {formatPrice(product.originalPrice)}
            </span>
          </div>
          <Button
            size="sm"
            variant="outline"
            className="rounded font-mono border-clay/40 text-clay hover:bg-clay hover:text-clay-foreground"
            onClick={handleAdd}
            aria-label={`将 ${product.name} 加入购物车`}
          >
            <ShoppingBag className="size-4" />
            <span className="hidden sm:inline">加入</span>
          </Button>
        </div>
      </div>
    </article>
  );
}