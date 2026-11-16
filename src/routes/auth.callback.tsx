import { useEffect, useState } from "react";
import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { toast } from "sonner";
import { TOKEN_KEY, fetchProfile } from "@/lib/api";

/** OAuth 回调中转页：从 URL 解析 token，存入 localStorage，拉取用户信息后跳转。 */
function AuthCallbackPage() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const token = params.get("token");
    const redirect = params.get("redirect") || "/";

    if (!token) {
      toast.error("授权失败，请重试");
      navigate({ to: "/login" });
      return;
    }

    localStorage.setItem(TOKEN_KEY, token);

    fetchProfile()
      .then((user) => {
        localStorage.setItem("xiaoxia.user", JSON.stringify(user));
        navigate({ to: redirect.startsWith("/") ? redirect : "/" });
      })
      .catch(() => {
        localStorage.removeItem(TOKEN_KEY);
        toast.error("登录信息获取失败，请重试");
        navigate({ to: "/login" });
      })
      .finally(() => setLoading(false));
  }, [navigate]);

  return (
    <div className="grid min-h-screen place-items-center">
      <div className="text-center">
        <div className="mx-auto size-8 animate-spin rounded-full border-2 border-clay border-t-transparent" />
        <p className="mt-4 text-sm text-muted-foreground">
          {loading ? "正在登录…" : ""}
        </p>
      </div>
    </div>
  );
}

export const Route = createFileRoute("/auth/callback")({
  component: AuthCallbackPage,
});
