import { useState } from "react";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { useNavigate } from "@tanstack/react-router";
import { toast } from "sonner";
import { z } from "zod";
import { register as apiRegister, toErrorMessage } from "@/lib/api";
import { useAuth } from "./auth-provider";
import { TRACK_EVENTS, track } from "@/lib/observability";

const loginSchema = z.object({
  email: z.string().email("请输入有效邮箱"),
  password: z.string().min(1, "请输入密码"),
});

const registerSchema = z
  .object({
    name: z.string().min(1, "请输入用户名"),
    email: z.string().email("请输入有效邮箱"),
    password: z.string().min(6, "密码至少 6 位"),
    confirm: z.string().min(1, "请再次输入密码"),
  })
  .refine((v) => v.password === v.confirm, {
    message: "两次密码不一致",
    path: ["confirm"],
  });

export type LoginForm = z.infer<typeof loginSchema>;
export type RegisterForm = z.infer<typeof registerSchema>;

/** 登录前记录的 redirect（如 "/checkout?ids=1,2"）解析为路径与查询参数。 */
export function resolveRedirect(redirect: string | undefined): {
  path: string;
  search: Record<string, string>;
} | null {
  if (!redirect || !redirect.startsWith("/")) return null;
  const [path, queryPart] = redirect.split("?");
  const search: Record<string, string> = {};
  if (queryPart) {
    new URLSearchParams(queryPart).forEach((value, key) => {
      search[key] = value;
    });
  }
  return { path, search };
}

/**
 * 登录 / 注册表单：校验规则、提交、错误回填与登录后回跳。
 * 页面组件只消费返回的状态与动作。
 */
export function useAuthForms(redirect: string | undefined) {
  const navigate = useNavigate();
  const { login } = useAuth();
  const [resetOpen, setResetOpen] = useState(false);

  const loginForm = useForm<LoginForm>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: "", password: "" },
  });

  const registerForm = useForm<RegisterForm>({
    resolver: zodResolver(registerSchema),
    defaultValues: { name: "", email: "", password: "", confirm: "" },
  });

  const goAfterAuth = async (user?: { is_admin?: boolean; is_finance?: boolean }) => {
    const target = resolveRedirect(redirect);
    if (target) {
      await navigate({ to: target.path, search: target.search });
      return;
    }
    if (user?.is_admin) {
      await navigate({ to: "/admin" });
    } else if (user?.is_finance) {
      await navigate({ to: "/admin/finance" });
    } else {
      await navigate({ to: "/" });
    }
  };

  const onLogin = loginForm.handleSubmit(async (values) => {
    try {
      const user = await login(values.email, values.password);
      track(TRACK_EVENTS.login);
      await goAfterAuth(user);
    } catch (err) {
      loginForm.setError("password", {
        type: "manual",
        message: toErrorMessage(err, "登录失败，请检查邮箱密码"),
      });
    }
  });

  const onRegister = registerForm.handleSubmit(async (values) => {
    try {
      await apiRegister(values.name, values.email, values.password);
      toast.success("注册成功，正在登录…");
      const user = await login(values.email, values.password);
      track(TRACK_EVENTS.register);
      await goAfterAuth(user);
    } catch (err) {
      registerForm.setError("email", {
        type: "manual",
        message: toErrorMessage(err, "注册失败，请重试"),
      });
    }
  });

  return {
    loginForm,
    registerForm,
    onLogin,
    onRegister,
    resetOpen,
    setResetOpen,
  };
}
