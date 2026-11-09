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
import { ResetPasswordDialog } from "../reset-password-dialog";
import { useAuthForms } from "../use-auth-forms";

/** 登录 / 注册页：表单逻辑在 useAuthForms，组件只负责渲染。 */
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

          <Tabs defaultValue="login" className="mt-6">
            <TabsList className="w-full">
              <TabsTrigger value="login" className="flex-1">
                登录
              </TabsTrigger>
              <TabsTrigger value="register" className="flex-1">
                注册
              </TabsTrigger>
            </TabsList>

            <TabsContent value="login">
            <Form {...loginForm}>
              <form onSubmit={onLogin} className="mt-6 space-y-4">
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
            </TabsContent>

            <TabsContent value="register">
            <Form {...registerForm}>
              <form onSubmit={onRegister} className="mt-6 space-y-4">
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
            </TabsContent>
          </Tabs>
        </div>
      </div>

      <ResetPasswordDialog open={resetOpen} onOpenChange={setResetOpen} />
    </SiteLayout>
  );
}