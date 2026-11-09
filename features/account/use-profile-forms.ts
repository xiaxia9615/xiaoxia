import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { changePassword, updateProfile } from "@/lib/api";
import { useAuth } from "./auth-provider";

const profileSchema = z.object({
  nickname: z.string().min(1, "昵称不能为空").max(20, "昵称最长 20 字"),
  email: z.string().email("邮箱格式不正确").optional().or(z.literal("")),
  sex: z.union([z.literal(0), z.literal(1), z.literal(2)]),
  birthday: z.string().optional().or(z.literal("")),
});

const passwordSchema = z
  .object({
    oldPassword: z.string().min(1, "请输入原密码"),
    newPassword: z.string().min(6, "新密码至少 6 位"),
    confirm: z.string().min(1, "请再次输入新密码"),
  })
  .refine((v) => v.newPassword === v.confirm, {
    message: "两次输入的新密码不一致",
    path: ["confirm"],
  });

export type ProfileFormValues = z.infer<typeof profileSchema>;
export type PasswordFormValues = z.infer<typeof passwordSchema>;

/**
 * 个人资料：资料与密码两组表单、提交与反馈。
 * 页面组件只消费本 hook 暴露的状态与动作。
 */
export function useProfileForms() {
  const { user, updateUser } = useAuth();
  const [tab, setTab] = useState("info");

  const initialSex: 0 | 1 | 2 = user?.sex === 1 || user?.sex === 2 ? user.sex : 0;

  const infoForm = useForm<ProfileFormValues>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      nickname: user?.nickname ?? "",
      email: user?.email ?? "",
      sex: initialSex,
      birthday: user?.birthday ?? "",
    },
  });

  const passwordForm = useForm<PasswordFormValues>({
    resolver: zodResolver(passwordSchema),
    defaultValues: { oldPassword: "", newPassword: "", confirm: "" },
  });

  const profileMutation = useMutation({
    mutationFn: (values: ProfileFormValues) =>
      updateProfile({
        nickname: values.nickname,
        email: values.email,
        sex: values.sex,
        birthday: values.birthday,
      }),
    onSuccess: (next) => {
      updateUser(next);
      toast.success("资料已更新");
    },
    onError: () => toast.error("保存失败，请重试"),
  });

  const passwordMutation = useMutation({
    mutationFn: (values: PasswordFormValues) =>
      changePassword(values.oldPassword, values.newPassword),
    onSuccess: () => {
      passwordForm.reset({ oldPassword: "", newPassword: "", confirm: "" });
      toast.success("密码修改成功");
    },
    onError: () => toast.error("修改失败", { description: "请确认原密码是否正确" }),
  });

  return {
    user,
    tab,
    setTab,
    infoForm,
    passwordForm,
    profileMutation,
    passwordMutation,
    onSubmitProfile: infoForm.handleSubmit((values) => profileMutation.mutate(values)),
    onSubmitPassword: passwordForm.handleSubmit((values) => passwordMutation.mutate(values)),
  };
}
