/** 加入状态在浏览器本地的键名（仅记录“我已加入”标记）。 */
export const JOINED_STORAGE_KEY = "xiaoxia.contribute.joined";

export function readJoinedFlag(): boolean {
  if (typeof window === "undefined") return false;
  try {
    return window.localStorage.getItem(JOINED_STORAGE_KEY) === "1";
  } catch {
    return false;
  }
}

export function writeJoinedFlag(joined: boolean): void {
  if (typeof window === "undefined") return;
  try {
    if (joined) {
      window.localStorage.setItem(JOINED_STORAGE_KEY, "1");
    } else {
      window.localStorage.removeItem(JOINED_STORAGE_KEY);
    }
  } catch {
    // 隐私模式等不可写场景：忽略，仅影响标记展示
  }
}
