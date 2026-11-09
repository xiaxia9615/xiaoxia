import { useState } from "react";
import { ImageOff } from "lucide-react";
import { cn } from "@/lib/utils";

/**
 * 统一图片组件：懒加载 + 异步解码 + 失败兜底 + 淡入。
 *
 * 相比裸 <img> 解决的问题：
 * 1. 默认 loading="lazy"，首屏只加载视口内图片；
 * 2. decoding="async" 避免解码阻塞主线程；
 * 3. 失败时显示占位而非浏览器默认裂图，并可选中上报；
 * 4. 首屏关键图（priority）跳过懒加载并提高抓取优先级。
 */
export function AppImage({
  src,
  alt,
  className,
  wrapperClassName,
  priority = false,
  ratio,
}: {
  src?: string;
  alt: string;
  className?: string;
  wrapperClassName?: string;
  /** 首屏关键图：不懒加载，优先抓取 */
  priority?: boolean;
  /** 宽高比（如 "1/1"、"4/3"），用于预留空间避免布局偏移 */
  ratio?: string;
}) {
  const [failed, setFailed] = useState(false);
  const [loaded, setLoaded] = useState(false);

  const style = ratio ? { aspectRatio: ratio } : undefined;

  if (!src || failed) {
    return (
      <span
        style={style}
        className={cn(
          "grid place-items-center bg-surface text-muted-foreground/60",
          wrapperClassName,
          className,
        )}
        aria-label={alt}
        role="img"
      >
        <ImageOff className="size-5" />
      </span>
    );
  }

  return (
    <span style={style} className={cn("contents", wrapperClassName)}>
      <img
        src={src}
        alt={alt}
        loading={priority ? "eager" : "lazy"}
        decoding="async"
        fetchPriority={priority ? "high" : "auto"}
        onLoad={() => setLoaded(true)}
        onError={() => setFailed(true)}
        className={cn(
          "transition-opacity duration-300",
          loaded ? "opacity-100" : "opacity-0",
          className,
        )}
      />
    </span>
  );
}
