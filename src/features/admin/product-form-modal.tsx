import { useEffect } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { zodResolver } from "@hookform/resolvers/zod";
import { Loader2 } from "lucide-react";
import { useForm } from "react-hook-form";
import { toast } from "sonner";
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
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
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
  uploadProductCover,
  type AdminProductItem,
  type ProductPayload,
} from "@/lib/api/admin";
import { getFieldErrors, toErrorMessage } from "@/lib/api/request";
import {
  EMPTY_FORM,
  PRODUCT_TYPES,
  SERVER_FIELD_MAP,
  schema,
  type ProductForm,
} from "./product-form-modal.schema";

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

  const nature = form.watch("nature");

  useEffect(() => {
    if (open) {
      form.clearErrors();
      form.reset(
        editing
          ? {
              name: editing.name,
              priceYuan: (editing.price / 100).toFixed(2),
              type: String(editing.type),
              nature: editing.nature,
              quotaYuan: editing.quota > 0 ? (editing.quota / 100).toFixed(2) : "",
              skillId: editing.skill_id !== null ? String(editing.skill_id) : "",
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
        nature: values.nature,
        cover: values.cover.trim() || undefined,
      };
      if (values.nature === "virtual") {
        payload.quota = Math.round(Number(values.quotaYuan) * 100);
        payload.skill_id = Number(values.skillId);
      }
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
              name="nature"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-300">商品性质</FormLabel>
                  <FormControl>
                    <RadioGroup
                      value={field.value}
                      onValueChange={field.onChange}
                      className="flex gap-6"
                    >
                      <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-200">
                        <RadioGroupItem value="physical" className="border-emerald-400/40 text-emerald-400" />
                        实物商品
                      </label>
                      <label className="flex cursor-pointer items-center gap-2 text-sm text-slate-200">
                        <RadioGroupItem value="virtual" className="border-emerald-400/40 text-emerald-400" />
                        虚拟服务
                      </label>
                    </RadioGroup>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
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
            {nature === "virtual" ? (
              <div className="grid grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="quotaYuan"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-slate-300">赠送额度（元）</FormLabel>
                      <FormControl>
                        <Input
                          type="number"
                          min="0.01"
                          step="0.01"
                          inputMode="decimal"
                          placeholder="支付后自动发放到买家余额"
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
                  name="skillId"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel className="text-slate-300">绑定技能 ID</FormLabel>
                      <FormControl>
                        <Input
                          type="number"
                          min="1"
                          step="1"
                          inputMode="numeric"
                          placeholder="如 9"
                          className={fieldClass}
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            ) : null}
            <FormField
              control={form.control}
              name="cover"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className="text-slate-300">封面图</FormLabel>
                  <FormControl>
                    <div className="space-y-2">
                      <div className="flex gap-2">
                        <Input
                          placeholder="粘贴 URL 或点击右侧上传"
                          className={fieldClass}
                          value={field.value ?? ""}
                          onChange={field.onChange}
                          onBlur={field.onBlur}
                          name={field.name}
                          ref={field.ref}
                        />
                        <label className="inline-flex cursor-pointer items-center rounded-md border border-input bg-background px-3 text-sm hover:bg-accent">
                          上传
                          <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            className="hidden"
                            onChange={async (e) => {
                              const file = e.target.files?.[0];
                              e.target.value = "";
                              if (!file) return;
                              try {
                                const { url } = await uploadProductCover(file);
                                field.onChange(url);
                                toast.success("封面图上传成功");
                              } catch (err) {
                                toast.error(err instanceof Error ? err.message : "上传失败");
                              }
                            }}
                          />
                        </label>
                      </div>
                      {field.value && (
                        <img
                          src={field.value}
                          alt="封面预览"
                          className="h-20 w-20 rounded border object-cover"
                          onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = "none"; }}
                        />
                      )}
                    </div>
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
