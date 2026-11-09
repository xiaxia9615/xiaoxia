import { useEffect } from "react";
import { useNavigate } from "@tanstack/react-router";
import { useAuth } from "@/features/account/auth-provider";

/**
 * 受保护页面登录守卫。
 *
 * 未登录（且登录态校验完成）时跳转登录页并携带 redirect；
 * 返回 ready=false 表示页面应渲染空/骨架，调用方据此短路渲染。
 *
 * @param redirect 登录后回跳地址（默认当前路由，支持带查询参数）
 */
export function useRequireLogin(redirect: string): boolean {
  const { user, loading } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (!loading && !user) {
      void navigate({ to: "/login", search: { redirect } });
    }
  }, [loading, user, navigate, redirect]);

  return !loading && Boolean(user);
}
