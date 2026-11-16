import { Link } from "@tanstack/react-router";
import { ArrowRight, Newspaper } from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { fetchArticles, fetchProducts } from "@/lib/api";
import { ProductCard } from "@/features/catalog/product-card";
import { AppImage } from "@/components/system/app-image";
import { EIGHT_TRIGRAMS } from "../bagua";

/** 节标题：朱砂短线 + 宋体标题 + 右侧英文小标。 */
export function SectionHead({
  title,
  code,
  action,
  to,
}: {
  title: string;
  code: string;
  action?: string;
  to?: string;
}) {
  return (
    <div className="mb-8 flex items-end justify-between gap-4">
      <div>
        <div className="flex items-center gap-3">
          <span className="h-4 w-1 bg-[var(--zhen-cinnabar)]" />
          <h2 className="zhen-seraph text-2xl font-bold tracking-wide text-ink sm:text-3xl">{title}</h2>
        </div>
        <p className="ml-4 mt-1 font-mono text-[11px] uppercase tracking-widest text-muted-foreground">{code}</p>
      </div>
      {action && to && (
        <Link
          to={to}
          className="inline-flex items-center gap-1.5 font-mono text-xs text-clay transition-colors hover:text-clay/70"
        >
          {action}
          <ArrowRight className="size-3.5" />
        </Link>
      )}
    </div>
  );
}

/** 四象限服务位：取代原先的图标网格，改为卦爻式阵列。 */
export function FourDomains() {
  const domains = [
    { gua: "乾", name: "正品保障", desc: "源头严选，每一物皆可溯源", code: "auth.verify" },
    { gua: "离", name: "极速配送", desc: "现货当天发出，链路实时可查", code: "order.ship" },
    { gua: "坎", name: "七天无忧退", desc: "不满意可退换，逆向链路完整", code: "order.refund" },
    { gua: "巽", name: "AI 导购", desc: "说预算与场景，替你挑合适的那一件", code: "guide.chat" },
  ];
  return (
    <section className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
      <SectionHead title="四象·服务" code="four domains · service matrix" />
      <div className="grid gap-px border border-border/70 bg-border/70 sm:grid-cols-2 lg:grid-cols-4">
        {domains.map((d) => (
          <div key={d.name} className="group bg-card p-6 transition-colors hover:bg-surface">
            <div className="zhen-seraph text-3xl text-clay transition-colors group-hover:text-[var(--zhen-cinnabar-bright)]">
              {d.gua}
            </div>
            <h3 className="mt-4 font-display text-base font-semibold text-ink">{d.name}</h3>
            <p className="mt-2 text-sm leading-6 text-muted-foreground">{d.desc}</p>
            <p className="mt-4 font-mono text-[10px] uppercase tracking-widest text-muted-foreground/60">{d.code}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

/** 精选商品：真实调用后端商品接口，推荐位优先，不足时取前 4 个。 */
export function FeaturedGoods() {
  const { data, isLoading } = useQuery({
    queryKey: ["home-featured-goods"],
    queryFn: () => fetchProducts({ page: 1, limit: 12 }),
  });

  const list = data?.list ?? [];
  const featured = list.filter((p) => p.featured);
  const picked = (featured.length > 0 ? featured : list).slice(0, 4);

  return (
    <section className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
      <SectionHead title="坎位·好物" code="goods · curated selection" action="查看全部" to="/products" />
      {isLoading ? (
        <div className="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
          {[0, 1, 2, 3].map((i) => (
            <div key={i} className="aspect-[3/4] animate-pulse rounded-lg bg-muted" />
          ))}
        </div>
      ) : picked.length === 0 ? (
        <div className="border border-dashed border-border/70 bg-card/50 p-8 text-center text-sm text-muted-foreground">
          好物筹备中，敬请期待
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
          {picked.map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      )}
    </section>
  );
}

/** 导购横幅：太极概念延展为「问阵」。 */
export function GuideBanner() {
  return (
    <section className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
      <div className="relative overflow-hidden border border-[var(--zhen-line)] bg-[var(--zhen-bg)] px-6 py-12 sm:px-12">
        <div className="zhen-paper pointer-events-none absolute inset-0 opacity-70" />
        {/* 右侧淡印太极 */}
        <svg
          viewBox="0 0 200 200"
          className="pointer-events-none absolute -right-8 -top-8 size-56 opacity-15"
          aria-hidden="true"
        >
          <circle cx={100} cy={100} r={92} fill="none" stroke="var(--zhen-gold)" strokeWidth={1} />
          <path d="M100,8 a92,92 0 0,1 0,184 a46,46 0 0,1 0,-92 a46,46 0 0,0 0,-92" fill="var(--zhen-gold)" />
          <circle cx={100} cy={54} r={14} fill="var(--zhen-gold)" />
        </svg>

        <div className="relative max-w-xl">
          <div className="zhen-seal size-10 text-lg font-bold">问</div>
          <h2 className="zhen-seraph mt-5 text-3xl font-bold leading-snug text-[var(--zhen-ink)]">
            不知选哪一件？入阵问一问
          </h2>
          <p className="mt-3 max-w-md text-sm leading-7 text-[var(--zhen-ink-dim)]">
            告诉它预算、场景与喜好。导购会按你的条件在商品域里推演，
            给出最合适的那一件。
          </p>
          <Link
            to="/assistant"
            className="mt-6 inline-flex items-center gap-2 border border-[var(--zhen-cinnabar)] px-6 py-3 font-mono text-sm text-[var(--zhen-cinnabar-bright)] transition-colors hover:bg-[var(--zhen-cinnabar)] hover:text-white"
          >
            开始问阵
            <ArrowRight className="size-4" />
          </Link>
        </div>
      </div>
    </section>
  );
}

/** 八卦域图：把接口契约按域摆开，硬核用户可直接对照。 */
export function DomainMatrix() {
  return (
    <section className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
      <SectionHead title="八卦·域图" code="eight domains · contract map" />
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {EIGHT_TRIGRAMS.map((g) => (
          <div
            key={g.name}
            className="group border border-border/70 bg-card p-5 transition-colors hover:border-clay/50"
          >
            <div className="flex items-baseline justify-between">
              <span className="zhen-seraph text-2xl text-ink group-hover:text-clay">{g.name}</span>
              <span className="font-mono text-[10px] uppercase tracking-widest text-muted-foreground">{g.symbol}</span>
            </div>
            <p className="mt-3 font-mono text-xs text-clay">{g.domain}</p>
            <p className="mt-1 text-xs text-muted-foreground">{g.nature}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

/** 生活志内容区：结构与原文一致，仅换视觉标题。 */
export function LifeJournal() {
  const { data } = useQuery({
    queryKey: ["articles"],
    queryFn: () => fetchArticles({ page: 1, limit: 3 }),
  });
  const list = data?.list ?? [];

  return (
    <section className="mx-auto max-w-6xl px-4 py-14 sm:px-6">
      <SectionHead title="生活志" code="journal · field notes" action="查看全部" to="/articles" />
      {list.length === 0 ? (
        <div className="grid gap-6 border border-dashed border-border/70 bg-card/50 p-8 text-center sm:grid-cols-3">
          {["挑选好物的灵感", "生活的仪式感", "我们的品牌故事"].map((t) => (
            <div key={t} className="flex flex-col items-center gap-2 text-muted-foreground">
              <Newspaper className="size-6 opacity-50" />
              <p className="text-sm font-medium text-ink">{t}</p>
              <p className="text-xs">内容筹备中，敬请期待</p>
            </div>
          ))}
        </div>
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {list.map((a) => (
            <Link
              key={a.id}
              to="/articles/$articleId"
              params={{ articleId: String(a.id) }}
              className="group overflow-hidden border border-border/70 bg-card transition-colors hover:border-clay/50"
            >
              <div className="aspect-[16/10] w-full overflow-hidden bg-surface">
                {a.cover ? (
                  <AppImage
                    src={a.cover}
                    alt={a.title}
                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                  />
                ) : (
                  <div className="grid h-full w-full place-items-center text-muted-foreground/40">
                    <Newspaper className="size-9" />
                  </div>
                )}
              </div>
              <div className="p-5">
                <h3 className="zhen-seraph text-lg leading-snug text-ink group-hover:text-clay">{a.title}</h3>
                {a.summary && <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">{a.summary}</p>}
              </div>
            </Link>
          ))}
        </div>
      )}
    </section>
  );
}
