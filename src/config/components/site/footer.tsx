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
            <li>
              <a
                href="https://github.com/xiaxia9615/xiaoxia"
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1.5 transition-colors hover:text-clay"
              >
                <svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true" className="shrink-0"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                GitHub 开源仓库
              </a>
            </li>
          </ul>
        </div>
      </div>
      <div className="border-t border-border/60 py-5 text-center font-mono text-xs text-muted-foreground">
        <p>© {new Date().getFullYear()} 小夏生活 · {t("footer.tagline")}</p>
        <p className="mt-1">
          <a
            href="https://beian.miit.gov.cn/"
            target="_blank"
            rel="noopener noreferrer"
            className="transition-colors hover:text-clay"
          >
            苏ICP备2024132572号
          </a>
        </p>
      </div>
    </footer>
  );
}