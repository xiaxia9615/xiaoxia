import { useEffect, useRef, useState, type ReactNode } from "react";
import { cn } from "@/lib/utils";

type Direction = "up" | "left" | "right" | "scale";

const DIR_CLASS: Record<Direction, string> = {
  up: "animate-fade-up",
  left: "animate-fade-in-left",
  right: "animate-fade-in-right",
  scale: "animate-scale-in",
};

/**
 * 滚动进入视口后播放入场动画（只触发一次）。入场前以 opacity-0
 * 占位避免闪现；不支持 IntersectionObserver 时直接显示。
 * prefers-reduced-motion 由全局规则兜底为瞬时完成。
 */
export function ScrollReveal({
  children,
  direction = "up",
  delay = 0,
  className,
}: {
  children: ReactNode;
  direction?: Direction;
  delay?: number;
  className?: string;
}) {
  const ref = useRef<HTMLDivElement>(null);
  const [visible, setVisible] = useState(
    typeof IntersectionObserver === "undefined",
  );

  useEffect(() => {
    if (typeof IntersectionObserver === "undefined") return;
    const el = ref.current;
    if (!el) return;
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setVisible(true);
          observer.unobserve(el);
        }
      },
      { threshold: 0.15 },
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  return (
    <div
      ref={ref}
      className={cn(
        "will-change-[opacity,transform]",
        !visible && "opacity-0",
        visible && DIR_CLASS[direction],
        className,
      )}
      style={{ animationDelay: visible ? `${delay}s` : undefined }}
    >
      {children}
    </div>
  );
}

/** 数字滚动到目标值；target 变化时从当前值继续过渡到新目标。 */
export function useCountUp(target: number, duration = 800): number {
  const [value, setValue] = useState(0);
  const valueRef = useRef(0);

  useEffect(() => {
    const from = valueRef.current;
    if (from === target) {
      setValue(target);
      return;
    }
    const start = performance.now();
    let raf = 0;
    const tick = (now: number) => {
      const p = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - p, 3);
      const next = Math.round(from + (target - from) * eased);
      valueRef.current = next;
      setValue(next);
      if (p < 1) raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(raf);
  }, [target, duration]);

  return value;
}