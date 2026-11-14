import { useEffect, useState } from "react";

/**
 * 终端打字机效果 hook：挂载后逐字打出 text，打完后保持完整文本。
 * 返回当前已打出的子串。
 */
export function useTypewriter(text: string, charIntervalMs = 40): string {
  const [len, setLen] = useState(0);

  useEffect(() => {
    setLen(0);
    const timer = window.setInterval(() => {
      setLen((prev) => {
        if (prev >= text.length) {
          window.clearInterval(timer);
          return prev;
        }
        return prev + 1;
      });
    }, charIntervalMs);
    return () => window.clearInterval(timer);
  }, [text, charIntervalMs]);

  return text.slice(0, len);
}
