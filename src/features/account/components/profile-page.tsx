import { ArrowLeft, KeyRound, Save, UserRound } from "lucide-react";
import { useNavigate } from "@tanstack/react-router";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useProfileForms } from "../use-profile-forms";

/** 个人资料页：表单逻辑在 useProfileForms，组件只负责渲染。 */
export function ProfilePage() {
  const navigate = useNavigate();
  const {
    user,
    tab,
    setTab,
    infoForm,
    passwordForm,
    profileMutation,
    passwordMutation,
    onSubmitProfile,
    onSubmitPassword,
  } = useProfileForms();

  return (
    <main className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
      <button
        onClick={() => navigate({ to: "/" })}
        className="mb-6 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-ink"
      >
        <ArrowLeft className="size-4" />
        返回首页
      </button>

      <div className="mb-8 flex items-center gap-4">
        <span className="grid size-14 place-items-center rounded-lg bg-clay text-clay-foreground">
          <UserRound className="size-7" />
        </span>
        <div>
          <h1 className="font-display text-2xl font-bold text-ink">个人资料</h1>
          <p className="text-sm text-muted-foreground">账号：{user?.username}</p>
        </div>
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList className="grid w-full max-w-sm grid-cols-2">
          <TabsTrigger value="info">
            <UserRound className="size-4" />
            基本资料
          </TabsTrigger>
          <TabsTrigger value="password">
            <KeyRound className="size-4" />
            修改密码
          </TabsTrigger>
        </TabsList>

        <TabsContent value="info">
          <Card>
            <CardHeader>
              <CardTitle>基本资料</CardTitle>
            </CardHeader>
            <CardContent>
              <Form {...infoForm}>
                <form
                  onSubmit={onSubmitProfile}
                  className="space-y-5"
                >
                  <FormField
                    control={infoForm.control}
                    name="nickname"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>昵称</FormLabel>
                        <FormControl>
                          <Input placeholder="你的昵称" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={infoForm.control}
                    name="email"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>邮箱</FormLabel>
                        <FormControl>
                          <Input placeholder="you@example.com" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <div className="space-y-2">
                    <Label>性别</Label>
                    <div className="flex gap-3">
                      {[
                        { v: 0, label: "保密" },
                        { v: 1, label: "男" },
                        { v: 2, label: "女" },
                      ].map((o) => (
                        <label
                          key={o.v}
                          className="flex items-center gap-2 rounded border border-border px-4 py-2 text-sm cursor-pointer has-[:checked]:border-clay has-[:checked]:bg-clay/10"
                        >
                          <input
                            type="radio"
                            className="accent-clay"
                            checked={infoForm.watch("sex") === o.v}
                            onChange={() => infoForm.setValue("sex", o.v as 0 | 1 | 2)}
                          />
                          {o.label}
                        </label>
                      ))}
                    </div>
                  </div>
                  <FormField
                    control={infoForm.control}
                    name="birthday"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>生日</FormLabel>
                        <FormControl>
                          <Input type="date" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <Button
                    type="submit"
                    className="rounded"
                    disabled={profileMutation.isPending}
                  >
                    <Save className="size-4" />
                    {profileMutation.isPending ? "保存中…" : "保存资料"}
                  </Button>
                </form>
              </Form>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="password">
          <Card>
            <CardHeader>
              <CardTitle>修改密码</CardTitle>
            </CardHeader>
            <CardContent>
              <Form {...passwordForm}>
                <form
                  onSubmit={onSubmitPassword}
                  className="space-y-5"
                >
                  <FormField
                    control={passwordForm.control}
                    name="oldPassword"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>原密码</FormLabel>
                        <FormControl>
                          <Input type="password" placeholder="请输入原密码" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={passwordForm.control}
                    name="newPassword"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>新密码</FormLabel>
                        <FormControl>
                          <Input type="password" placeholder="至少 6 位" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <FormField
                    control={passwordForm.control}
                    name="confirm"
                    render={({ field }) => (
                      <FormItem>
                        <FormLabel>确认新密码</FormLabel>
                        <FormControl>
                          <Input type="password" placeholder="再次输入新密码" {...field} />
                        </FormControl>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                  <Button
                    type="submit"
                    className="rounded"
                    disabled={passwordMutation.isPending}
                  >
                    <KeyRound className="size-4" />
                    {passwordMutation.isPending ? "提交中…" : "修改密码"}
                  </Button>
                </form>
              </Form>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </main>
  );
}
