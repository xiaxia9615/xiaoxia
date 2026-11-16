import { useState, useEffect, useCallback } from "react";
import { Link, useNavigate } from "@tanstack/react-router";
import { ArrowLeft, Sparkles, Code, MessageCircle, Wallet } from "lucide-react";
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
                      name="username"
                      render={({ field }) => (
                        <FormItem>
                          <FormLabel>账号</FormLabel>
                          <FormControl>
                            <Input placeholder="用户名 / 手机号" autoComplete="username" {...field} />
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
                        name="username"
                        render={({ field }) => (
                          <FormItem>
                            <FormLabel>账号</FormLabel>
                            <FormControl>
                              <Input placeholder="用户名 / 手机号" autoComplete="username" {...field} />
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
                className="grid size-10 place-items-center rounded-full border border-border/70 bg-card transition-colors hover:border-clay hover:text-clay"
                aria-label="GitHub 登录"
              >
                <Code className="size-5" />
              </button>
              <button
                type="button"
                onClick={() => { window.location.href = `${API_BASE}/v1/auth/redirect/weixin`; }}
                className="grid size-10 place-items-center rounded-full border border-border/70 bg-card transition-colors hover:border-clay hover:text-clay"
                aria-label="微信登录"
              >
                <MessageCircle className="size-5" />
              </button>
              <button
                type="button"
                onClick={() => { window.location.href = `${API_BASE}/v1/auth/redirect/alipay`; }}
                className="grid size-10 place-items-center rounded-full border border-border/70 bg-card transition-colors hover:border-clay hover:text-clay"
                aria-label="支付宝登录"
              >
                <Wallet className="size-5" />
              </button>
            </div>
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
