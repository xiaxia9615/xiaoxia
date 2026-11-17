import { useState, useEffect, useCallback } from "react";
import { Link, useNavigate } from "@tanstack/react-router";
import { ArrowLeft, Sparkles } from "lucide-react";
import { toast } from "sonner";
import { SiteLayout } from "@/components/site/layout";
import { Button } from "@/components/ui/button";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { API_BASE, smsSend, smsLogin, toErrorMessage } from "@/lib/api";
import { ResetPasswordDialog } from "../reset-password-dialog";
import { useAuthForms } from "../use-auth-forms";
import { TRACK_EVENTS, track } from "@/lib/observability";

/** GitHub Octocat SVG */
function GithubIcon() {
  return (
    <svg viewBox="0 0 24 24" className="size-5" fill="currentColor">
      <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57v-2.01c-3.345.72-4.05-1.605-4.05-1.605-.54-1.395-1.32-1.77-1.32-1.77-1.08-.735.09-.72.09-.72 1.2.09 1.83 1.23 1.83 1.23 1.065 1.815 2.795 1.29 3.475.99.105-.765.42-1.29.765-1.59-2.67-.3-5.475-1.335-5.475-5.94 0-1.312.465-2.385 1.23-3.225-.12-.3-.535-1.527.12-3.18 0 0 1.005-.322 3.285 1.23a11.25 11.25 0 0 1 3-.405c1.02 0 2.04.135 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.655 1.653.24 2.88.12 3.18.765.84 1.23 1.913 1.23 3.225 0 4.612-2.805 5.63-5.475 5.925.435.375.81 1.095.81 2.22v3.293c0 .315.225.69.825.57C20.565 21.795 24 17.305 24 12c0-6.63-5.37-12-12-12z"/>
    </svg>
  );
}

/** 微信 SVG */
function WeixinIcon() {
  return (
    <svg viewBox="0 0 24 24" className="size-5" fill="currentColor">
      <path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.333.333.333a.41.41 0 0 0 .193-.05l1.625-.95a.995.995 0 0 1 .5-.14c.095 0 .19.014.283.038a11.3 11.3 0 0 0 2.587.381c.168.012.334.024.503.024-.107-.348-.166-.71-.166-1.068 0-3.844 3.679-6.962 8.22-6.962.262 0 .52.012.778.024-.731-3.511-4.485-6.188-9.014-6.188zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.626 7.17c0-.651.52-1.18 1.16-1.18zm5.813 0c.642 0 1.162.529 1.162 1.818a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 9.452 7.817c0-.651.52-1.826 1.16-1.826z"/>
      <path d="M24 14.382c0-3.376-3.297-6.118-7.366-6.118-4.068 0-7.365 2.742-7.365 6.118 0 3.376 3.297 6.118 7.365 6.118.852 0 1.673-.123 2.434-.348a.79.79 0 0 1 .357-.04l1.324.776a.33.33 0 0 0 .157.04c.165 0 .27-.138.27-.27 0-.06-.024-.118-.04-.176l-.318-1.206a.472.472 0 0 1 .174-.542C22.953 18.234 24 16.428 24 14.382zm-9.797-1.024c.535 0 .969.44.969.982a.976.976 0 0 1-.969.982.976.976 0 0 1-.969-.982c0-.542.434-.982.969-.982zm4.862 0c.535 0 .969.44.969.982a.976.976 0 0 1-.969.982.976.976 0 0 1-.969-.982c0-.542.434-.982.969-.982z"/>
    </svg>
  );
}

/** 支付宝 SVG */
function AlipayIcon() {
  return (
    <svg viewBox="0 0 24 24" className="size-5" fill="currentColor">
      <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12c4.096 0 7.7-2.058 9.87-5.193l-4.732-2.07s-2.293 3.07-5.138 3.07c-3.03 0-5.137-2.107-5.137-5.137 0-3.03 2.107-5.137 5.137-5.137 2.845 0 5.138 3.07 5.138 7.463 3.274 0 7.463-3.274 7.463-7.463 0-4.189-3.274-7.463-7.463-7.463zm0 3.5c2.485 0 4.5 2.015 4.5 4.5S14.485 12.5 12 12.5 7.5 10.485 7.5 8s2.015-4.5 4.5-4.5z"/>
      <path d="M23.123 19.65c-2.3-1.06-5.297-2.51-8.123-3.823 1.18-1.873 2.093-3.983 2.622-5.827H12.5v-2h5.25v-1.5H12.5V4.5h-2v2H5v1.5h5.5v2H7v1.5h8.5c-.42 1.42-1.1 2.97-1.95 4.42-3.43-1.27-7.07-2.55-9.55-2.55-2.5 0-4 .87-4 2.5 0 2.5 3.5 4.5 8 4.5 2.5 0 5-1 7-2.5 3.5 1.5 8 3.5 10 4.5.5.25.5.25.5.25s.5-.5.5-.5z"/>
    </svg>
  );
}

/** 登录 / 注册页：支持手机号快捷登录、邮箱密码登录/注册、第三方 OAuth。 */
export function LoginPage({ redirect }: { redirect: string | undefined }) {
  const { loginForm, registerForm, onLogin, onRegister, resetOpen, setResetOpen } =
    useAuthForms(redirect);

  return (
    <SiteLayout>
      <div className="mx-auto flex max-w-md flex-col px-4 py-16">
        <Link
          to="/"
          className="mb-6 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-clay"
        >
          <ArrowLeft className="size-4" />
          返回首页
        </Link>

        <div className="rounded-xl border border-border/70 bg-card p-8 shadow-card">
          <span className="grid size-12 place-items-center rounded-lg bg-clay text-clay-foreground shadow-glow">
            <Sparkles className="size-6" />
          </span>
          <h1 className="mt-4 font-display text-2xl font-bold text-ink">小夏生活</h1>
          <p className="mt-1 text-sm text-muted-foreground">
            登录后可继续使用 AI 导购、购物车与下单功能
          </p>

          <Tabs defaultValue="phone" className="mt-6">
            <TabsList className="w-full">
              <TabsTrigger value="phone" className="flex-1">
                手机号登录
              </TabsTrigger>
              <TabsTrigger value="email" className="flex-1">
                邮箱密码
              </TabsTrigger>
            </TabsList>

            <TabsContent value="phone">
              <PhoneLoginForm redirect={redirect} />
            </TabsContent>

            <TabsContent value="email">
              <div className="mt-6 space-y-4">
                <Form {...loginForm}>
                  <form onSubmit={onLogin} className="space-y-4">
                    <FormField
                      control={loginForm.control}
                      name="email"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>邮箱</FormLabel>
                          <FormControl>
                            <Input placeholder="请输入邮箱" autoComplete="email" {...field} />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={loginForm.control}
                      name="password"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>密码</FormLabel>
                          <FormControl>
                            <Input
                              type="password"
                              placeholder="请输入密码"
                              autoComplete="current-password"
                              {...field}
                            />
                          </FormControl>
                          <FormMessage />
                        </FormItem>
                      )}
                    />
                    <Button
                      type="submit"
                      size="lg"
                      className="w-full rounded"
                      disabled={loginForm.formState.isSubmitting}
                    >
                      {loginForm.formState.isSubmitting ? "登录中…" : "登录"}
                    </Button>
                    <button
                      type="button"
                      onClick={() => setResetOpen(true)}
                      className="w-full text-center text-sm text-muted-foreground transition-colors hover:text-clay"
                    >
                      忘记密码？
                    </button>
                  </form>
                </Form>

                <div className="border-t pt-4">
                  <p className="mb-3 text-center text-xs text-muted-foreground">还没有账号？立即注册</p>
                  <Form {...registerForm}>
                    <form onSubmit={onRegister} className="space-y-4">
                      <FormField
                        control={registerForm.control}
                        name="email"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>邮箱</FormLabel>
                            <FormControl>
                              <Input placeholder="请输入邮箱" autoComplete="email" {...field} />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <FormField
                        control={registerForm.control}
                        name="password"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>密码</FormLabel>
                            <FormControl>
                              <Input
                                type="password"
                                placeholder="至少 6 位"
                                autoComplete="new-password"
                                {...field}
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <FormField
                        control={registerForm.control}
                        name="confirm"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>确认密码</FormLabel>
                            <FormControl>
                              <Input
                                type="password"
                                placeholder="再次输入密码"
                                autoComplete="new-password"
                                {...field}
                              />
                            </FormControl>
                            <FormMessage />
                          </FormItem>
                        )}
                      />
                      <Button
                        type="submit"
                        size="lg"
                        className="w-full rounded"
                        disabled={registerForm.formState.isSubmitting}
                      >
                        {registerForm.formState.isSubmitting ? "注册中…" : "注册并登录"}
                      </Button>
                    </form>
                  </Form>
                </div>
              </div>
            </TabsContent>
          </Tabs>

          <div className="mt-6 border-t pt-6">
            <p className="mb-3 text-center text-xs text-muted-foreground">第三方登录</p>
            <div className="flex justify-center gap-4">
              <button
                type="button"
                onClick={() => { window.location.href = `${API_BASE}/v1/auth/redirect/github`; }}
                className="grid size-10 place-items-center rounded-full border border-border/70 bg-card text-zinc-400 transition-colors hover:border-zinc-900 hover:bg-zinc-900 hover:text-white"
                aria-label="GitHub 登录"
              >
                <GithubIcon />
              </button>
              <button
                type="button"
                onClick={() => { window.location.href = `${API_BASE}/v1/auth/redirect/weixin`; }}
                className="grid size-10 place-items-center rounded-full border border-border/70 bg-card text-zinc-400 transition-colors hover:border-[#07C160] hover:bg-[#07C160] hover:text-white"
                aria-label="微信登录"
              >
                <WeixinIcon />
              </button>
              <button
                type="button"
                onClick={() => { window.location.href = `${API_BASE}/v1/auth/redirect/alipay`; }}
                className="grid size-10 place-items-center rounded-full border border-border/70 bg-card text-zinc-400 transition-colors hover:border-[#1677FF] hover:bg-[#1677FF] hover:text-white"
                aria-label="支付宝登录"
              >
                <AlipayIcon />
              </button>
            </div>
            <p className="mt-4 text-center text-xs text-muted-foreground">
              未注册的手机号验证后将自动创建账号
            </p>
          </div>
        </div>
      </div>

      <ResetPasswordDialog open={resetOpen} onOpenChange={setResetOpen} />
    </SiteLayout>
  );
}

/** 手机号验证码登录表单（带 60 秒倒计时）。 */
function PhoneLoginForm({ redirect }: { redirect: string | undefined }) {
  const navigate = useNavigate();
  const [phone, setPhone] = useState("");
  const [code, setCode] = useState("");
  const [countdown, setCountdown] = useState(0);
  const [sending, setSending] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    if (countdown <= 0) return;
    const timer = setInterval(() => {
      setCountdown((c) => Math.max(0, c - 1));
    }, 1000);
    return () => clearInterval(timer);
  }, [countdown]);

  const handleSendCode = useCallback(async () => {
    if (!/^1[3-9]\d{9}$/.test(phone)) {
      toast.error("请输入正确的手机号");
      return;
    }
    if (countdown > 0) return;
    setSending(true);
    try {
      await smsSend(phone);
      toast.success("验证码已发送");
      setCountdown(60);
    } catch (err) {
      toast.error(toErrorMessage(err, "验证码发送失败"));
    } finally {
      setSending(false);
    }
  }, [phone, countdown]);

  const handleSmsLogin = useCallback(async (e: React.FormEvent) => {
    e.preventDefault();
    if (!/^1[3-9]\d{9}$/.test(phone)) {
      toast.error("请输入正确的手机号");
      return;
    }
    if (code.length !== 6) {
      toast.error("请输入 6 位验证码");
      return;
    }
    setSubmitting(true);
    try {
      const { token, user } = await smsLogin(phone, code);
      localStorage.setItem("xiaoxia.token", token);
      localStorage.setItem("xiaoxia.user", JSON.stringify(user));
      track(TRACK_EVENTS.login);
      const target = redirect && redirect.startsWith("/") ? redirect : "/";
      await navigate({ to: target });
    } catch (err) {
      toast.error(toErrorMessage(err, "登录失败，请检查验证码"));
    } finally {
      setSubmitting(false);
    }
  }, [phone, code, redirect, navigate]);

  return (
    <form onSubmit={handleSmsLogin} className="mt-6 space-y-4">
      <div className="space-y-2">
        <label className="text-sm font-medium text-foreground">手机号</label>
        <Input
          placeholder="请输入手机号"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
          maxLength={11}
        />
      </div>
      <div className="space-y-2">
        <label className="text-sm font-medium text-foreground">验证码</label>
        <div className="flex gap-2">
          <Input
            placeholder="6 位验证码"
            value={code}
            onChange={(e) => setCode(e.target.value)}
            maxLength={6}
          />
          <Button
            type="button"
            variant="outline"
            disabled={sending || countdown > 0}
            onClick={handleSendCode}
            className="shrink-0"
          >
            {countdown > 0 ? `${countdown}s` : "获取验证码"}
          </Button>
        </div>
      </div>
      <Button
        type="submit"
        size="lg"
        className="w-full rounded"
        disabled={submitting}
      >
        {submitting ? "登录中…" : "手机号登录"}
      </Button>
    </form>
  );
}
