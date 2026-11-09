import { Link } from "@tanstack/react-router";
import { ArrowRight } from "lucide-react";
import { BaguaCompass } from "./bagua-compass";
import { StreamTerminal } from "./stream-terminal";

/** 主视觉：左文案 + 竖排朱印题签，右阵盘 + 数据流终端。 */
export function ZhenHero() {
  return (
    <section className="zhen-root relative overflow-hidden">
      {/* 宣纸底纹 */}
      <div className="zhen-paper pointer-events-none absolute inset-0" />
      {/* 朱砂晕染：仅作为底层氛围，不抢阵盘 */}
      <div
        className="pointer-events-none absolute -left-40 top-1/3 size-[420px] rounded-full opacity-25 blur-3xl"
        style={{ backgroundColor: "var(--zhen-cinnabar)" }}
      />

      <div className="relative mx-auto grid max-w-6xl items-center gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_1.1fr] lg:py-20">
        <div>
          {/* 题签 */}
          <div className="flex items-center gap-3 font-mono text-[11px] tracking-widest text-[var(--zhen-ink-dim)]">
            <span className="h-px w-8 bg-[var(--zhen-cinnabar)]" />
            XIAOXIA · DIGITAL ARRAY
          </div>

          <h1 className="zhen-seraph mt-6 text-5xl font-black leading-[1.15] tracking-wide text-[var(--zhen-ink)] sm:text-6xl">
            万物互联
            <br />
            <span className="text-[var(--zhen-cinnabar-bright)]">交易成阵</span>
          </h1>

          <p className="mt-6 max-w-md text-sm leading-7 text-[var(--zhen-ink-dim)]">
            以阴阳为序，以八卦为域。账号、商品、购物车、订单、支付，
            五个域各居其位，一次交易即是一轮阵法推演。
            从请求入阵到支付归位，每一步皆可观测。
          </p>

          <div className="mt-8 flex flex-wrap items-center gap-3">
            <Link
              to="/products"
              className="group inline-flex items-center gap-2 bg-[var(--zhen-cinnabar)] px-6 py-3 font-mono text-sm text-white transition-colors hover:bg-[var(--zhen-cinnabar-bright)]"
            >
              入阵选购
              <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
            </Link>
            <Link
              to="/assistant"
              className="inline-flex items-center gap-2 border border-[var(--zhen-line)] px-6 py-3 font-mono text-sm text-[var(--zhen-ink)] transition-colors hover:border-[var(--zhen-gold)] hover:text-[var(--zhen-gold)]"
            >
              问问导购
            </Link>
          </div>

          {/* 五域指标 */}
          <dl className="mt-10 grid grid-cols-3 gap-6 border-t border-[var(--zhen-line)] pt-6 font-mono">
            {[
              { k: "domains", v: "5", label: "主链路域" },
              { k: "contracts", v: "45", label: "接口契约" },
              { k: "gate", v: "8", label: "架构门禁" },
            ].map((s) => (
              <div key={s.k}>
                <dt className="text-[10px] uppercase tracking-widest text-[var(--zhen-ink-dim)]">{s.k}</dt>
                <dd className="mt-1 text-2xl text-[var(--zhen-gold)]">{s.v}</dd>
                <dd className="mt-0.5 text-[11px] text-[var(--zhen-ink-dim)]">{s.label}</dd>
              </div>
            ))}
          </dl>
        </div>

        <div>
          <BaguaCompass />
          <div className="mx-auto mt-6 max-w-[420px]">
            <StreamTerminal />
          </div>
        </div>
      </div>

      {/* 右侧竖排题签：仅宽屏显示 */}
      <div className="zhen-vertical zhen-seraph pointer-events-none absolute right-6 top-16 hidden text-2xl text-[var(--zhen-line)] xl:block">
        先天为体 · 后天为用
      </div>
    </section>
  );
}
