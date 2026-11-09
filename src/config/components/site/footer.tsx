import { useTranslation } from "react-i18next";
import { Link } from "@tanstack/react-router";
import { Sparkles } from "lucide-react";

export function Footer() {
  const { t } = useTranslation();
  return (
    <footer className="mt-20 border-t border-border bg-terminal/40">
      <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3">
        <div>
          <div className="flex items-center gap-2">
            <span className="grid size-8 place-items-center rounded bg-terminal text-clay shadow-scan">
              <Sparkles className="size-4" />
            </span>
            <span className="font-display text-base font-bold text-ink">小夏生活</span>
          </div>
          <p className="mt-3 max-w-xs text-sm leading-6 text-muted-foreground">
            用心挑选有温度的生活好物，愿每一件器物都让日常变得更值得。
          </p>
        </div>

        <div>
          <h3 className="text-sm font-semibold text-ink">逛逛</h3>
          <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
            <li><Link to="/products" className="transition-colors hover:text-clay">全部好物</Link></li>
            <li><Link to="/assistant" className="transition-colors hover:text-clay">AI 导购助手</Link></li>
            <li><Link to="/cart" className="transition-colors hover:text-clay">购物车</Link></li>
          </ul>
        </div>

        <div>
          <h3 className="text-sm font-semibold text-ink">关于</h3>
          <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
            <li>联系客服：sixia@qssxmall.cn</li>
            <li>服务时间：每日 9:00 – 21:00</li>
            <li>配送范围：全国大部分地区</li>
          </ul>
        </div>
      </div>
      <div className="border-t border-border/60 py-5 text-center font-mono text-xs text-muted-foreground">
        <p>© {new Date().getFullYear()} 小夏生活 · {t("footer.tagline")}</p>
      </div>
    </footer>
  );
}