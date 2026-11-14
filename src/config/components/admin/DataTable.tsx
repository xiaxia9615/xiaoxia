import type { ReactNode } from "react";
import { Skeleton } from "@/components/ui/skeleton";

/** 列配置：key 取行数据字段，header 渲染表头，cell 自定义单元格渲染。 */
export interface DataTableColumn<Row> {
  key: string;
  header: ReactNode;
  className?: string;
  cell: (row: Row) => ReactNode;
}

interface DataTableProps<Row> {
  columns: Array<DataTableColumn<Row>>;
  data: Row[];
  isLoading?: boolean;
  /** 空状态文案 */
  emptyText?: string;
  /** 分页插槽：由调用方渲染分页控件 */
  pagination?: ReactNode;
}

/** 极简数据表格：白底、细边框、行 hover；Loading 骨架屏与空状态内置。 */
export function DataTable<Row>({
  columns,
  data,
  isLoading = false,
  emptyText = "暂无数据",
  pagination,
}: DataTableProps<Row>) {
  return (
    <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-gray-200 bg-gray-50/60">
              {columns.map((col) => (
                <th
                  key={col.key}
                  className={
                    "px-4 py-3 text-left text-xs font-medium tracking-wide text-gray-500 " +
                    (col.className ?? "")
                  }
                >
                  {col.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr>
                <td colSpan={columns.length} className="px-4 py-3">
                  <div className="space-y-3 py-1">
                    {Array.from({ length: 5 }).map((_, i) => (
                      <div key={i} className="flex items-center gap-4">
                        {columns.map((col) => (
                          <Skeleton
                            key={col.key}
                            className={"h-4 flex-1 " + (col.className ?? "")}
                          />
                        ))}
                      </div>
                    ))}
                  </div>
                </td>
              </tr>
            ) : data.length === 0 ? (
              <tr>
                <td
                  colSpan={columns.length}
                  className="px-4 py-16 text-center text-sm text-gray-400"
                >
                  {emptyText}
                </td>
              </tr>
            ) : (
              data.map((row, i) => (
                <tr
                  key={i}
                  className="border-b border-gray-100 transition-colors last:border-b-0 hover:bg-gray-50/70"
                >
                  {columns.map((col) => (
                    <td
                      key={col.key}
                      className={
                        "px-4 py-3 text-gray-700 " + (col.className ?? "")
                      }
                    >
                      {col.cell(row)}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
      {pagination ? (
        <div className="border-t border-gray-200 px-4 py-3">{pagination}</div>
      ) : null}
    </div>
  );
}
