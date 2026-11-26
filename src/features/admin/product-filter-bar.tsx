import { Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

/** 分类筛选选项（与后端 type 对应：1 技能 / 2 商品）。 */
const TYPE_FILTERS: Array<{ value: string; label: string }> = [
  { value: "all", label: "全部分类" },
  { value: "1", label: "技能" },
  { value: "2", label: "商品" },
];

/** 商品列表工具栏：关键词搜索 + 分类筛选。 */
export function ProductFilterBar({
  keywordInput,
  onKeywordInputChange,
  onSearch,
  typeFilter,
  onTypeFilterChange,
}: {
  keywordInput: string;
  onKeywordInputChange: (value: string) => void;
  onSearch: () => void;
  typeFilter: string;
  onTypeFilterChange: (value: string) => void;
}) {
  return (
    <div className="mb-4 flex flex-wrap items-center gap-2">
      <form
        className="flex items-center gap-2"
        onSubmit={(e) => {
          e.preventDefault();
          onSearch();
        }}
      >
        <div className="relative">
          <Search className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
          <Input
            value={keywordInput}
            onChange={(e) => onKeywordInputChange(e.target.value)}
            placeholder="搜索商品名称，回车确认"
            className="w-64 border-white/10 bg-black/40 pl-8 text-slate-100 placeholder:text-slate-600 focus-visible:border-emerald-400/50 focus-visible:ring-emerald-400/30"
          />
        </div>
        <Button type="submit" variant="outline" size="sm">
          搜索
        </Button>
      </form>
      <Select value={typeFilter} onValueChange={onTypeFilterChange}>
        <SelectTrigger className="w-36 border-white/10 bg-black/40 text-slate-100 focus:border-emerald-400/50 focus:ring-emerald-400/30">
          <SelectValue placeholder="分类" />
        </SelectTrigger>
        <SelectContent className="border-white/10 bg-zinc-950/95 backdrop-blur-xl">
          {TYPE_FILTERS.map((t) => (
            <SelectItem key={t.value} value={t.value}>
              {t.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  );
}
