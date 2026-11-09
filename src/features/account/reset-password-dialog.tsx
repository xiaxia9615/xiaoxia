import { useEffect, useRef, useState } from "react";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { resetPassword, sendVerifyCode, toErrorMessage } from "@/lib/api";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";

const resetSchema = z
  .object({
    phone: z.string().min(1, "请输入手机号"),
    code: z.string().min(1, "请输入验证码"),
    newPassword: z.string().min(6, "新密码至少 6 位"),
    confirm: z.string().min(1, "请再次输入新密码"),
  })
  .refine((v) => v.newPassword === v.confirm, {
    message: "两次输入的新密码不一致",
    path: ["confirm"],
  });

type ResetForm = z.infer<typeof resetSchema>;

/** 找回密码弹窗：手机号验证码重置，成功后关闭并提示用新密码登录。 */
export function ResetPasswordDialog({
  open,
  onOpenChange,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const [sending, setSending] = useState(false);
  const [countdown, setCountdown] = useState(0);
  const timerRef = useRef<number | null>(null);

  const form = useForm<ResetForm>({
    resolver: zodResolver(resetSchema),
    defaultValues: { phone: "", code: "", newPassword: "", confirm: "" },
  });

  const phone = form.watch("phone");

  // 关闭时清理倒计时并重置表单
  useEffect(() => {
    if (!open) {
      if (timerRef.current) window.clearInterval(timerRef.current);
      timerRef.current = null;
      setCountdown(0);
      form.reset();
    }
  }, [open, form]);

  useEffect(() => () => {
    if (timerRef.current) window.clearInterval(timerRef.current);
  }, []);

  const handleSend = async () => {
    const valid = await form.trigger("phone");
    if (!valid) return;
    setSending(true);
    try {
      await sendVerifyCode(phone.trim());
      toast.success("验证码已发送");
      setCountdown(60);
      timerRef.current = window.setInterval(() => {
        setCountdown((c) => {
          if (c <= 1) {
            if (timerRef.current) window.clearInterval(timerRef.current);
            timerRef.current = null;
            return 0;
          }
          return c - 1;
        });
      }, 1000);
    } catch (err) {
      const msg = toErrorMessage(err, "");
      toast.error("验证码发送失败", { description: msg || "请稍后重试" });
    } finally {
      setSending(false);
    }
  };

  const onSubmit = form.handleSubmit(async (values) => {
    try {
      await resetPassword({
        phone: values.phone.trim(),
        code: values.code.trim(),
        newPassword: values.newPassword,
      });
      toast.success("密码重置成功，请使用新密码登录");
      onOpenChange(false);
    } catch (err) {
      const msg = toErrorMessage(err, "");
      form.setError("code", {
        type: "manual",
        message: msg || "重置失败，请检查验证码",
      });
    }
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>找回密码</DialogTitle>
          <DialogDescription>
            输入注册手机号，获取验证码后设置新密码。
          </DialogDescription>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4">
            <FormField
              control={form.control}
              name="phone"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>手机号</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="请输入注册手机号"
                      inputMode="tel"
                      autoComplete="tel"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="code"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>验证码</FormLabel>
                  <FormControl>
                    <div className="flex gap-2">
                      <Input
                        placeholder="请输入验证码"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        className="flex-1"
                        {...field}
                      />
                      <Button
                        type="button"
                        variant="outline"
                        className="shrink-0 rounded"
                        onClick={() => void handleSend()}
                        disabled={sending || countdown > 0}
                      >
                        {countdown > 0 ? `${countdown}s` : sending ? "发送中…" : "获取验证码"}
                      </Button>
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="newPassword"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>新密码</FormLabel>
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
              control={form.control}
              name="confirm"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>确认新密码</FormLabel>
                  <FormControl>
                    <Input
                      type="password"
                      placeholder="再次输入新密码"
                      autoComplete="new-password"
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <DialogFooter>
              <Button
                type="submit"
                className="rounded"
                disabled={form.formState.isSubmitting}
              >
                {form.formState.isSubmitting ? "提交中…" : "重置密码"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
