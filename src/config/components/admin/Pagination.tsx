import { Button } from "@/components/ui/button";

/** Admin 后台通用分页控件（上一页/下一页 + 页码信息）。 */
export function Pagination({
  page,
  lastPage,
  total,
  onChange,
}: {
  page: number;
  lastPage: number;
  total: number;
  onChange: (page: number) => void;
}) {
  if (lastPage <= 1) return null;
  return (
    <div className="flex items-center justify-between text-sm text-gray-500">
      <span>
        第 {page} / {lastPage} 页，共 {total} 条
      </span>
      <div className="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          disabled={page <= 1}
          onClick={() => onChange(Math.max(1, page - 1))}
        >
          上一页
        </Button>
        <Button
          variant="outline"
          size="sm"
          disabled={page >= lastPage}
          onClick={() => onChange(Math.min(lastPage, page + 1))}
        >
          下一页
        </Button>
      </div>
    </div>
  );
}
