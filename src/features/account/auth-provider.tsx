import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from "react";
import { toast } from "sonner";
import { AUTH_EXPIRED_EVENT, TOKEN_KEY, fetchProfile, login as apiLogin, type User } from "@/lib/api";

const USER_KEY = "xiaoxia.user";

interface AuthState {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<User>;
  logout: () => void;
  updateUser: (user: User) => void;
}

const AuthContext = createContext<AuthState | null>(null);

function readStoredUser(): User | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem(USER_KEY);
    return raw ? (JSON.parse(raw) as User) : null;
  } catch {
    return null;
  }
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(() => readStoredUser());
  const [loading, setLoading] = useState(true);

  // 启动时若存在 token，则拉取用户信息校验登录态
  useEffect(() => {
    let active = true;
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) {
      setLoading(false);
      return;
    }
    fetchProfile()
      .then((profile) => {
        if (active) {
          setUser(profile);
          localStorage.setItem(USER_KEY, JSON.stringify(profile));
        }
      })
      .catch(() => {
        if (active) {
          // token 失效则清除
          localStorage.removeItem(TOKEN_KEY);
          localStorage.removeItem(USER_KEY);
          setUser(null);
        }
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => {
      active = false;
    };
  }, []);

  // 会话中任意请求返回 401 时统一登出、提示并强制跳转登录页（携带 redirect 回跳参数）
  useEffect(() => {
    const onExpired = () => {
      setUser((prev) => {
        if (prev) {
          localStorage.removeItem("xiaoxia.user");
          toast.error("登录已过期，请重新登录");
        }
        return null;
      });
      // 强制跳转登录页，记录当前路径用于登录后回跳（hash 路由）
      const current = window.location.hash.replace(/^#/, "") || "/";
      if (current !== "/login") {
        window.location.hash = `#/login?redirect=${encodeURIComponent(current)}`;
      }
    };
    window.addEventListener(AUTH_EXPIRED_EVENT, onExpired);
    return () => window.removeEventListener(AUTH_EXPIRED_EVENT, onExpired);
  }, []);

  const login = useCallback(async (email: string, password: string) => {
    const { token, user } = await apiLogin(email, password);
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    setUser(user);
    return user;
  }, []);

  const logout = useCallback(() => {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    setUser(null);
  }, []);

  const updateUser = useCallback((next: User) => {
    localStorage.setItem(USER_KEY, JSON.stringify(next));
    setUser(next);
  }, []);

  // 启动校验期间渲染全屏 Loading，绝不闪现子页面骨架（如 Admin 侧边栏）
  if (loading) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background">
        <div className="flex flex-col items-center gap-3">
          <div
            className="h-8 w-8 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent"
            aria-hidden
          />
          <p className="text-sm text-muted-foreground">正在加载…</p>
        </div>
      </div>
    );
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, updateUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth 必须在 AuthProvider 内使用");
  }
  return ctx;
}