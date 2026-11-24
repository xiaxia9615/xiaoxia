import { TOKEN_KEY, UID_KEY } from "./api/request";

export const initWechatAuth = () => {
  const isWechat = /MicroMessenger/i.test(navigator.userAgent);
  if (!isWechat) return;

  const urlParams = new URLSearchParams(window.location.search);
  const code = urlParams.get("code");

  // 如果有 code，调接口换 Token
  if (code) {
    fetch("/api/v1/auth/wechat-mini-login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ code })
    })
    .then(res => res.json())
    .then(data => {
      if (data.code === 0 && data.data?.token) {
        // 与普通登录保持完全一致的存储方式
        localStorage.setItem(TOKEN_KEY, data.data.token);
        if (data.data.uid && data.data.uid !== "0") {
          localStorage.setItem(UID_KEY, data.data.uid);
        }
        
        // 清理 URL 上的 code，刷新回首页
        window.location.href = window.location.origin + window.location.pathname + window.location.hash;
      } else {
        console.error("微信登录失败:", data.message);
      }
    })
    .catch(err => console.error("请求失败:", err));
    return;
  }
};
