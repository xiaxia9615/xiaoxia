import { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { zodResolver } from "@hookform/resolvers/zod";
import { Loader2 } from "lucide-react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  createAdminProduct,
  updateAdminProduct,
  type AdminProductItem,
  type ProductPayload,
} from "@/lib/api/admin";
import { getFieldErrors, toErrorMessage } from "@/lib/api/request";

/** 分类占位选项（后端 type: 1 技能 / 2 商品；categories 接口当前为空，待后端补全后替换）。 */
const PRODUCT_TYPES: Array<{ value: string; label: string }> = [
  { value: "1", label: "技能" },
  { value: "2", label: "商品" },
];

const schema = z.object({
  name: z.string().min(1, "商品名称不能为空").max(255, "商品名称不能超过 255 字"),
  priceYuan: z
    .string()
    .min(1, "请输入价格")
    .refine((v) => Number.isFinite(Number(v)) && Number(v) > 0, "价格必须大于 0"),
  type: z.string().min(1, "请选择分类"),
  cover: z.string(),
  description: z.string(),
});

type ProductForm = z.infer<typeof schema>;

const EMPTY_FORM: ProductForm = {
  name: "",
  priceYuan: "",
  type: "2",
  cover: "",
  description: "",
};

/** 表单字段 -> 后端字段映射，用于精准回填 422 错误。 */
const SERVER_FIELD_MAP: Record<string, keyof ProductForm> = {
  name: "name",
  price: "priceYuan",
  type: "type",
  cover: "cover",
  description: "description",
};

/** 极客风输入样式：深色底 + emerald 聚焦光环。 */
const fieldClass =
  "border-white/10 bg-black/40 text-slate-100 placeholder:text-slate-600 transition-colors focus-visible:border-emerald-400/50 focus-visible:ring-emerald-400/30";

/** 商品新建 / 编辑弹窗：editing 为空时新建，成功后失效 admin-products 查询。 */
export function ProductFormModal({
  open,
  onOpenChange,
  editing,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  editing: AdminProductItem | null;
}) {
  const queryClient = useQueryClient();
  const form = useForm<ProductForm>({
    resolver: zodResolver(schema),
    defaultValues: EMPTY_FORM,
    mode: "onChange",
  });

  useEffect(() => {
    if (open) {
      form.clearErrors();
      form.reset(
        editing
          ? {
              name: editing.name,
              priceYuan: (editing.price / 100).toFixed(2),
              type: String(editing.type),
              cover: editing.cover ?? "",
              description: editing.description ?? "",
            }
          : EMPTY_FORM,
      );
    }
  }, [open, editing, form]);

  const mutation = useMutation({
    mutationFn: async (values: ProductForm) => {
      const payload: ProductPayload = {
        name: values.name.trim(),
        description: values.description.trim() || undefined,
        price: Math.round(Number(values.priceYuan) * 100),
        type: Number(values.type),
        cover: values.cover.trim() || undefined,
      };
      if (editing) {
        return updateAdminProduct(editing.id, payload);
      }
      return createAdminProduct({ ...payload, status: 1 });
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["admin-products"] });
      toast.success(editing ? "商品已更新" : "商品已创建", {
        description: editing ? "商品信息已保存并同步到列表。" : "新商品已上架并同步到列表。",
      });
      onOpenChange(false);
    },
    onError: (err) => {
      const fields = getFieldErrors(err);
      if (fields) {
        for (const [serverField, message] of Object.entries(fields)) {
          const clientField = SERVER_FIELD_MAP[serverField];
          if (clientField) {
            form.setError(clientField, { type: "server", message });
          }
        }
        toast.error("表单校验未通过", { description: "请检查标红字段后重新提交。" });
        return;
      }
      toast.error(editing ? "更新商品失败" : "创建商品失败", {
        description: toErrorMessage(err),
      });
    },
  });

  const onSubmit = form.handleSubmit((values) => mutation.mutate(values));

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="border-white/10 bg-zinc-950/90 shadow-[0_0_60px_-12px_rgba(16,185,129,0.35)] backdrop-blur-xl sm:max-w-lg">
        <DialogHeader>
          <DialogTitle className="text-slate-100">
            {editing ? "编辑商品" : "新建商品"}
          </DialogTitle>
          <DialogDescription className="text-slate-500">
            {editing ? `正在修改商品 #${editing.id}，保存后立即生效。` : "填写商品信息，创建后默认上架。"}
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4">
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-300">商品名称</FormLabel>
                  <FormControl>
                    <Input placeholder="请输入商品名称" className={fieldClass} {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="priceYuan"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-slate-300">价格（元）</FormLabel>
                    <FormControl>
                      <Input
                        type="number"
                        min="0.01"
                        step="0.01"
                        inputMode="decimal"
                        placeholder="0.00"
                        className={fieldClass}
                        {...field}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="type"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel className="text-slate-300">分类</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <FormControl>
                        <SelectTrigger className={fieldClass}>
                          <SelectValue placeholder="请选择分类" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent className="border-white/10 bg-zinc-950/95 backdrop-blur-xl">
                        {PRODUCT_TYPES.map((t) => (
                          <SelectItem key={t.value} value={t.value}>
                            {t.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            <FormField
              control={form.control}
              name="cover"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-300">封面图 URL</FormLabel>
                  <FormControl>
                    <Input
                      placeholder="https://example.com/cover.png（暂用文本占位）"
                      className={fieldClass}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-300">描述</FormLabel>
                  <FormControl>
                    <Textarea
                      rows={3}
                      placeholder="请输入商品描述（可选）"
                      className={fieldClass}
                      {...field}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <DialogFooter>
              <Button
                type="button"
                variant="outline"
                className="border-white/10 bg-transparent text-slate-300 hover:bg-white/5"
                disabled={mutation.isPending}
                onClick={() => onOpenChange(false)}
              >
                取消
              </Button>
              <Button
                type="submit"
                disabled={mutation.isPending}
                className="bg-emerald-600 text-white hover:bg-emerald-500"
              >
                {mutation.isPending ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    提交中…
                  </>
                ) : editing ? (
                  "保存修改"
                ) : (
                  "创建商品"
                )}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
