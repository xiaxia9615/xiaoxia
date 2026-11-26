import { Link } from "@tanstack/react-router";
import { ArrowLeft, Sparkles } from "lucide-react";
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
import { API_BASE } from "@/lib/api";
import { ResetPasswordDialog } from "../reset-password-dialog";
import { useAuthForms } from "../use-auth-forms";
import { AlipayIcon, GithubIcon, WeixinIcon } from "./login-icons";
import { PhoneLoginForm } from "./phone-login-form";
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

