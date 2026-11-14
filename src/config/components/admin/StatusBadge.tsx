import { cn } from "@/lib/utils";

/** 状态标签语义：success 绿 / warning 黄 / danger 红 / default 灰。 */
export type StatusBadgeType = "success" | "warning" | "danger" | "default";

const TYPE_STYLES: Record<StatusBadgeType, string> = {
  success: "bg-emerald-50 text-emerald-700 ring-emerald-600/20",
  warning: "bg-amber-50 text-amber-700 ring-amber-600/20",
  danger: "bg-red-50 text-red-700 ring-red-600/20",
  default: "bg-gray-100 text-gray-600 ring-gray-500/20",
};

const DOT_STYLES: Record<StatusBadgeType, string> = {
  success: "bg-emerald-500",
  warning: "bg-amber-500",
  danger: "bg-red-500",
  default: "bg-gray-400",
};

interface StatusBadgeProps {
  status: string;
  type?: StatusBadgeType;
  className?: string;
}

/** 极简状态标签：圆点 + 文案，细描边、浅底色。 */
export function StatusBadge({ status, type = "default", className }: StatusBadgeProps) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset",
        TYPE_STYLES[type],
        className,
      )}
    >
      <span className={cn("size-1.5 rounded-full", DOT_STYLES[type])} aria-hidden />
      {status}
    </span>
  );
}
