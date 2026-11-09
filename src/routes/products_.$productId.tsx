import { createFileRoute, Link } from "@tanstack/react-router";
import { ChevronRight, Heart, MessageCircleHeart, Minus, Plus, ShoppingBag, Star, Zap } from "lucide-react";
import { useState } from "react";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { formatPrice } from "@/data/products";
import { CommentsSection } from "@/features/catalog/comments-section";
import { useProductDetail } from "@/features/catalog/use-product-detail";
import { AppImage } from "@/components/system/app-image";

export const Route = createFileRoute("/products_/$productId")({
  component: ProductDetail,
});

function ProductDetail() {
  const { productId } = Route.useParams();
  const [activeImg, setActiveImg] = useState(0);
  const {
    product,
    isFetching,
    favorited,
    toggleFavorite,
    addToCart,
    buyNow,
    buying,
    qty,
    setQty,
  } = useProductDetail(productId);

  if (isFetching && !product) {
    return (
      <SiteLayout>
        <div className="mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:gap-14">
          <Skeleton className="aspect-square w-full rounded-xl" />
          <div className="space-y-4 pt-4">
            <Skeleton className="h-6 w-24" />
            <Skeleton className="h-10 w-2/3" />
            <Skeleton className="h-24 w-full" />
            <Skeleton className="h-14 w-1/2" />
          </div>
        </div>
      </SiteLayout>
    );
  }

  if (!product) {
    return (
      <SiteLayout>
        <div className="mx-auto flex max-w-6xl flex-col items-center px-4 py-28 text-center">
          <h1 className="font-display text-2xl font-bold text-ink">商品不存在</h1>
          <p className="mt-2 text-sm text-muted-foreground">
            该商品可能已下架或链接有误。
          </p>
          <Button asChild className="mt-6 rounded">
            <Link to="/products">返回选购</Link>
          </Button>
        </div>
      </SiteLayout>
    );
  }

  if (isFetching && !product) {
    return (
      <SiteLayout>
        <div className="mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:gap-14">
          <Skeleton className="aspect-square w-full rounded-xl" />
          <div className="space-y-4 pt-4">
            <Skeleton className="h-6 w-24" />
            <Skeleton className="h-10 w-2/3" />
            <Skeleton className="h-24 w-full" />
            <Skeleton className="h-14 w-1/2" />
          </div>
        </div>
      </SiteLayout>
    );
  }

  if (!product) {
    return (
      <SiteLayout>
        <div className="mx-auto flex max-w-6xl flex-col items-center px-4 py-28 text-center">
          <h1 className="font-display text-2xl font-bold text-ink">商品不存在</h1>
          <p className="mt-2 text-sm text-muted-foreground">
            该商品可能已下架或链接有误。
          </p>
          <Button asChild className="mt-6 rounded">
            <Link to="/products">返回选购</Link>
          </Button>
        </div>
      </SiteLayout>
    );
  }

  const gallery =
    product.images && product.images.length > 0 ? product.images : [product.image];

  return (
    <SiteLayout>
      <nav className="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
        <ol className="flex items-center gap-1.5 text-sm text-muted-foreground">
          <li>
            <Link to="/" className="transition-colors hover:text-clay">首页</Link>
          </li>
          <li><ChevronRight className="size-4" /></li>
          <li>
            <Link to="/products" className="transition-colors hover:text-clay">全部好物</Link>
          </li>
          <li><ChevronRight className="size-4" /></li>
          <li className="text-ink">{product.name}</li>
        </ol>
      </nav>

      <section className="mx-auto grid max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:gap-14">
        <div>
          <div className="overflow-hidden rounded-xl border border-border/60 bg-surface shadow-card">
            <AppImage
              src={gallery[activeImg]}
              alt={product.name}
              className="aspect-square w-full object-cover"
            />
          </div>
          {gallery.length > 1 && (
            <div className="mt-3 flex gap-3">
              {gallery.map((src, i) => (
                <button
                  key={src}
                  onClick={() => setActiveImg(i)}
                  className={`overflow-hidden rounded-xl border-2 transition-colors ${
                    i === activeImg ? "border-clay" : "border-transparent"
                  }`}
                  aria-label={`查看第 ${i + 1} 张图片`}
                >
                  <AppImage src={src} alt="" className="size-16 object-cover sm:size-20" />
                </button>
              ))}
            </div>
          )}
        </div>

        <div className="flex flex-col">
          <div className="flex items-center gap-2">
            <span className="rounded bg-clay/10 px-3 py-1 text-xs font-medium text-clay">
              {product.category === "drink"
                ? "茶饮器物"
                : product.category === "home"
                  ? "家居织物"
                  : "氛围陈设"}
            </span>
            {product.featured && (
              <span className="rounded bg-clay px-3 py-1 text-xs font-semibold text-clay-foreground">
                精选
              </span>
            )}
          </div>

          <h1 className="mt-4 font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">
            {product.name}
          </h1>
          <p className="mt-3 text-base leading-7 text-muted-foreground">
            {product.detail}
          </p>

          {product.rating > 0 && (
            <div className="mt-4 flex items-center gap-3 text-sm">
              <span className="flex items-center gap-1 font-medium text-ink">
                <Star className="size-4 fill-amber-400 text-amber-400" />
                {product.rating.toFixed(1)}
              </span>
              <span className="text-muted-foreground">·</span>
              <span className="text-muted-foreground">{product.reviews} 条评价</span>
            </div>
          )}

          <div className="mt-5 flex flex-wrap gap-2">
            {product.tags
              .filter((t) => !t.startsWith("http"))
              .map((tag) => (
                <span
                  key={tag}
                  className="rounded border border-border bg-card px-3 py-1 text-xs text-muted-foreground"
                >
                  {tag}
                </span>
              ))}
          </div>

          <div className="mt-6 flex items-baseline gap-3 rounded-lg bg-background px-5 py-4">
            <span className="font-display text-3xl font-black text-clay">
              {formatPrice(product.price)}
            </span>
            {product.originalPrice > product.price && (
              <span className="text-sm text-muted-foreground line-through">
                {formatPrice(product.originalPrice)}
              </span>
            )}
            {product.originalPrice > product.price && (
              <span className="ml-auto rounded bg-clay/10 px-2.5 py-1 text-xs font-medium text-clay">
                立省 {formatPrice(product.originalPrice - product.price)}
              </span>
            )}
          </div>

          <div className="mt-6 flex items-center gap-4">
            <span className="text-sm font-medium text-ink">数量</span>
            <div className="flex items-center rounded border border-border bg-card">
              <button
                onClick={() => setQty((v) => Math.max(1, v - 1))}
                className="grid size-10 place-items-center rounded text-muted-foreground transition-colors hover:text-clay"
                aria-label="减少数量"
              >
                <Minus className="size-4" />
              </button>
              <span className="w-8 text-center font-semibold text-ink">{qty}</span>
              <button
                onClick={() => setQty((v) => v + 1)}
                className="grid size-10 place-items-center rounded text-muted-foreground transition-colors hover:text-clay"
                aria-label="增加数量"
              >
                <Plus className="size-4" />
              </button>
            </div>
          </div>

          <div className="mt-8 flex flex-wrap gap-3">
            <Button
              size="lg"
              className="flex-1 rounded px-6"
              onClick={() => void buyNow()}
              disabled={buying}
            >
              <Zap className="size-4" />
              {buying ? "提交中…" : "立即购买"}
            </Button>
            <Button size="lg" variant="outline" className="flex-1 rounded px-6" onClick={() => void addToCart()}>
              <ShoppingBag className="size-4" />
              加入购物车
            </Button>
            <Button
              size="lg"
              variant={favorited ? "default" : "outline"}
              className="flex-1 rounded px-6"
              onClick={() => void toggleFavorite()}
            >
              <Heart className={`size-4 ${favorited ? "fill-current" : ""}`} />
              {favorited ? "已收藏" : "收藏"}
            </Button>
            <Button asChild size="lg" variant="ghost" className="flex-1 rounded px-6">
              <Link to="/assistant">
                <MessageCircleHeart className="size-4 text-clay" />
                咨询导购
              </Link>
            </Button>
          </div>
        </div>
      </section>
      <CommentsSection goodsId={Number(product.id)} />
    </SiteLayout>
  );
}