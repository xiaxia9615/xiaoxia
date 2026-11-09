import { Link } from "@tanstack/react-router";
import { Bell, Coins, Globe, Heart, History, LogOut, MapPin, Menu, Receipt, RotateCcw, ShoppingBag, Sparkles, Terminal, User, UserRound, X } from "lucide-react";
import { useState } from "react";
import { useTranslation } from "react-i18next";
import { LANGUAGES, setLanguage } from "@/i18n";
import { useAuth } from "@/features/account/auth-provider";
import { useCartState } from "@/features/cart/cart-provider";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { cn } from "@/lib/utils";


export function Header() {
  const [open, setOpen] = useState(false);
  const { t } = useTranslation();
  const { totalQty } = useCartState();
  const { user, logout } = useAuth();
  const navLinks = [
    { to: "/", label: t("nav.home") },
    { to: "/products", label: t("nav.products") },
    { to: "/articles", label: t("nav.content") },
    { to: "/assistant", label: t("nav.ai") },
    { to: "/contribute", label: t("nav.contribute"), emphasized: true },
  ];

  return (
    <header className="sticky top-0 z-40 border-b border-border bg-background/90 backdrop-blur-md">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <Link to="/" className="flex items-center gap-2" onClick={() => setOpen(false)}>
          <span className="grid size-9 place-items-center rounded bg-terminal text-clay shadow-scan">
            <Sparkles className="size-4.5" />
          </span>
          <span className="font-display text-lg font-bold tracking-tight text-ink">
            小夏生活
          </span>
        </Link>

        <nav className="hidden items-center gap-1 md:flex">
          {navLinks.map((link) => (
            <Link
              key={link.to}
              to={link.to}
              className={
                link.emphasized
                  ? "rounded bg-clay px-4 py-2 font-mono text-sm font-semibold text-clay-foreground shadow-glow transition-colors hover:bg-clay/90"
                  : "rounded px-4 py-2 font-mono text-sm font-medium text-muted-foreground transition-colors hover:bg-surface hover:text-clay"
              }
            >
              {link.label}
            </Link>
          ))}
        </nav>

        <div className="flex items-center gap-2">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="px-3" aria-label="切换语言">
                <Globe className="size-5" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              {LANGUAGES.map((lang) => (
                <DropdownMenuItem key={lang.code} onSelect={() => setLanguage(lang.code)}>
                  {lang.label}
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>

          <Button asChild variant="ghost" className="relative px-3">
            <Link to="/cart" aria-label="购物车">
              <ShoppingBag className="size-5" />
              {totalQty > 0 && (
                <span className="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-clay px-1 text-xs font-semibold text-clay-foreground">
                  {totalQty > 99 ? "99+" : totalQty}
                </span>
              )}
            </Link>
          </Button>

          {user ? (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button
                  className="flex items-center gap-2 rounded border border-border bg-surface py-1 pl-1 pr-3 font-mono transition-colors hover:border-clay/50"
                  aria-label="账户菜单"
                >
                  <Avatar className="size-8">
                    {user.avatar ? (
                      <AvatarImage src={user.avatar} alt={user.nickname} />
                    ) : (
                      <AvatarFallback className="bg-clay text-xs text-clay-foreground">
                        {user.nickname.slice(0, 1)}
                      </AvatarFallback>
                    )}
                  </Avatar>
                  <span className="hidden max-w-24 truncate text-sm font-medium text-ink sm:block">
                    {user.nickname}
                  </span>
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-44">
                <div className="px-2 py-1.5 text-sm font-medium text-ink">
                  {user.nickname}
                </div>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                  <Link to="/orders">
                    <Receipt className="size-4" />
                    我的订单
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link to="/messages">
                    <Bell className="size-4" />
                    消息中心
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link to="/integral">
                    <Coins className="size-4" />
                    我的积分
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link to="/aftersales">
                    <RotateCcw className="size-4" />
                    售后服务
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link to="/favorites">
                    <Heart className="size-4" />
                    我的收藏
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link to="/browse-history">
                    <History className="size-4" />
                    浏览记录
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link to="/address">
                    <MapPin className="size-4" />
                    收货地址
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link to="/pay-self-check">
                    <Terminal className="size-4" />
                    支付自检终端
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link to="/profile">
                    <UserRound className="size-4" />
                    个人资料
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onSelect={() => logout()} className="text-destructive">
                  <LogOut className="size-4" />
                  退出登录
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          ) : (
            <Button asChild variant="outline" className="hidden rounded font-mono sm:inline-flex">
              <Link to="/login">
                <User className="size-4" />
                登录
              </Link>
            </Button>
          )}

          <Button
            variant="ghost"
            className="px-2 md:hidden"
            onClick={() => setOpen((v) => !v)}
            aria-label={open ? "关闭菜单" : "打开菜单"}
          >
            {open ? <X className="size-5" /> : <Menu className="size-5" />}
          </Button>
        </div>
      </div>

      <div
        className={cn(
          "overflow-hidden border-t border-border/60 bg-background md:hidden",
          open ? "max-h-64" : "max-h-0",
        )}
        style={{ transition: "max-height 0.3s ease" }}
      >
        <nav className="flex flex-col gap-1 px-4 py-3">
          {navLinks.map((link) => (
            <Link
              key={link.to}
              to={link.to}
              className={
                link.emphasized
                  ? "rounded bg-clay/15 px-4 py-3 font-mono text-sm font-semibold text-clay transition-colors hover:bg-clay/25"
                  : "rounded px-4 py-3 font-mono text-sm font-medium text-foreground transition-colors hover:bg-surface hover:text-clay"
              }
              onClick={() => setOpen(false)}
            >
              {link.label}
            </Link>
          ))}
          {!user && (
            <Link
              to="/login"
              className="rounded px-4 py-3 font-mono text-sm font-medium text-clay"
              onClick={() => setOpen(false)}
            >
              登录
            </Link>
          )}
        </nav>
      </div>
    </header>
  );
}