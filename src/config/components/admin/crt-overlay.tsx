/**
 * CRT 显示器质感覆盖层：极细微扫描线 + 噪点 + 四角暗角。
 * 纯 CSS 渐变与内联 SVG 噪点实现，pointer-events-none 不影响交互。
 */
export function CrtOverlay() {
  return (
    <div
      aria-hidden
      className="pointer-events-none fixed inset-0 z-50 overflow-hidden"
    >
      {/* 横向扫描线（每 3px 一条 1px 线） */}
      <div
        className="absolute inset-0 opacity-[0.05]"
        style={{
          backgroundImage:
            "repeating-linear-gradient(0deg, rgba(16,185,129,0.6) 0px, rgba(16,185,129,0.6) 1px, transparent 1px, transparent 3px)",
        }}
      />
      {/* 噪点（内联 SVG feTurbulence，无外部资源） */}
      <div
        className="absolute inset-0 opacity-[0.04]"
        style={{
          backgroundImage:
            "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='120' height='120' filter='url(%23n)' opacity='0.7'/%3E%3C/svg%3E\")",
        }}
      />
      {/* 暗角：模拟 CRT 边缘亮度衰减 */}
      <div
        className="absolute inset-0"
        style={{
          background:
            "radial-gradient(ellipse at center, transparent 55%, rgba(0,0,0,0.38) 100%)",
        }}
      />
    </div>
  );
}
