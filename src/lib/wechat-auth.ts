import { TOKEN_KEY, UID_KEY } from "./api/request";

export const initWechatAuth = () => {
  const isWechat = /MicroMessenger/i.test(navigator.userAgent);
  if (!isWechat) return;

  const urlParams = new URLSearchParams(window.location.search);
  const code = urlParams.get("code");

  if (code) {
    window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
    fetch("/api/v1/auth/wechat-mini-login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ code })
    })
    .then(res => res.json())
    .then(data => {
      if (data.code === 0 && data.data?.token) {
        localStorage.setItem(TOKEN_KEY, data.data.token);
        if (data.data.uid && data.data.uid !== "0") {
          localStorage.setItem(UID_KEY, data.data.uid);
        }
        window.location.reload(); 
      } else {
        alert("❌ 微信登录接口报错：\n" + JSON.stringify(data));
      }
    })
    .catch(err => {
        alert("❌ 网络请求彻底失败：\n" + err.message);
    });
    return; 
  }
};
