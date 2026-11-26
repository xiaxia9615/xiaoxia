import { useState, useEffect, useCallback } from "react";
import { useNavigate } from "@tanstack/react-router";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { smsSend, smsLogin, toErrorMessage } from "@/lib/api";
import { TRACK_EVENTS, track } from "@/lib/observability";

/** 手机号验证码登录表单（带 60 秒倒计时）。 */
export function PhoneLoginForm({ redirect }: { redirect: string | undefined }) {
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
      if (redirect && redirect.startsWith("/")) {
        await navigate({ to: redirect });
      } else if (user.is_admin) {
        await navigate({ to: "/admin" });
      } else if (user.is_finance) {
        await navigate({ to: "/admin/finance" });
      } else {
        await navigate({ to: "/" });
      }
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
