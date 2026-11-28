import { useState } from "react";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { AUTH_FEATURES } from "@/lib/api";
import { updateUserProfile, changePassword } from "@/lib/api/user";
import { useAuth } from "./auth-provider";
import { toast } from "sonner";

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

export function useProfileForms() {
  const { user, updateUser, logout } = useAuth();
  const [tab, setTab] = useState("info");

  const initialSex: 0 | 1 | 2 =
    user?.sex === 1 || user?.sex === 2 ? user.sex : 0;

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

  const onSubmitProfile = infoForm.handleSubmit(async (values) => {
    if (!AUTH_FEATURES.profileEdit) return;
    try {
      await updateUserProfile({
        name: values.nickname,
        sex: values.sex,
        birthday: values.birthday || null,
      });
      if (user) {
        updateUser({
          ...user,
          name: values.nickname,
          nickname: values.nickname,
          sex: values.sex,
          birthday: values.birthday || undefined,
        });
      }
      toast.success("资料保存成功");
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "保存失败");
    }
  });

  const onSubmitPassword = passwordForm.handleSubmit(async (values) => {
    if (!AUTH_FEATURES.changePassword) return;
    try {
      await changePassword(values.oldPassword, values.newPassword);
      toast.success("密码修改成功，请重新登录");
      passwordForm.reset();
      logout();
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "修改失败");
    }
  });

  return {
    user,
    tab,
    setTab,
    infoForm,
    passwordForm,
    onSubmitProfile,
    onSubmitPassword,
  };
}
