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
import { Switch } from "@/components/ui/switch";
import {
  createAddress,
  updateAddress,
  type Address,
  type AddressInput,
} from "@/lib/api";

const schema = z.object({
  name: z.string().min(1, "请输入收货人姓名"),
  phone: z.string().min(1, "请输入手机号码"),
  province: z.string().min(1, "请输入省份"),
  city: z.string().min(1, "请输入城市"),
  district: z.string().min(1, "请输入区县"),
  detail: z.string().min(1, "请输入详细地址"),
  is_default: z.boolean(),
});

type AddressForm = z.infer<typeof schema>;

const EMPTY_FORM: AddressForm = {
  name: "",
  phone: "",
  province: "",
  city: "",
  district: "",
  detail: "",
  is_default: false,
};

/** 地址新增/编辑弹窗：editing 为空时新增，成功后失效 addresses 查询。 */
export function AddressDialog({
  open,
  onOpenChange,
  editing,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  editing: Address | null;
}) {
  const queryClient = useQueryClient();
  const form = useForm<AddressForm>({
    resolver: zodResolver(schema),
    defaultValues: EMPTY_FORM,
  });

  useEffect(() => {
    if (open) {
      form.reset(
        editing
          ? {
              name: editing.name,
              phone: editing.phone,
              province: editing.province,
              city: editing.city,
              district: editing.district,
              detail: editing.detail,
              is_default: editing.is_default,
            }
          : EMPTY_FORM,
      );
    }
  }, [open, editing, form]);

  const mutation = useMutation({
    mutationFn: async (values: AddressForm) => {
      const input: AddressInput = values;
      if (editing) {
        return updateAddress(editing.address_id, input);
      }
      return createAddress(input);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["addresses"] });
      toast.success(editing ? "地址已更新" : "地址已添加");
      onOpenChange(false);
    },
    onError: () => {
      toast.error(editing ? "更新地址失败" : "添加地址失败", {
        description: "请稍后重试，或检查网络连接。",
      });
    },
  });

  const onSubmit = form.handleSubmit((values) => mutation.mutate(values));

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{editing ? "编辑地址" : "新增收货地址"}</DialogTitle>
          <DialogDescription>
            填写完整的收货信息，方便我们准确送达。
          </DialogDescription>
        </DialogHeader>

        <Form {...form}>
          <form onSubmit={onSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>收货人</FormLabel>
                    <FormControl>
                      <Input placeholder="姓名" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="phone"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>手机号码</FormLabel>
                    <FormControl>
                      <Input placeholder="手机号" inputMode="tel" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            <div className="grid grid-cols-3 gap-4">
              <FormField
                control={form.control}
                name="province"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>省份</FormLabel>
                    <FormControl>
                      <Input placeholder="省" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="city"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>城市</FormLabel>
                    <FormControl>
                      <Input placeholder="市" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="district"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>区县</FormLabel>
                    <FormControl>
                      <Input placeholder="区/县" {...field} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            <FormField
              control={form.control}
              name="detail"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>详细地址</FormLabel>
                  <FormControl>
                    <Input placeholder="街道、门牌号等" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="is_default"
              render={({ field }) => (
                <FormItem className="flex items-center justify-between rounded-xl border border-border px-3 py-2">
                  <div>
                    <FormLabel className="text-sm font-medium">设为默认地址</FormLabel>
                  </div>
                  <FormControl>
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                      aria-label="设为默认地址"
                    />
                  </FormControl>
                </FormItem>
              )}
            />
            <DialogFooter>
              <Button
                type="submit"
                disabled={mutation.isPending}
                className="rounded"
              >
                {mutation.isPending && <Loader2 className="size-4 animate-spin" />}
                {editing ? "保存" : "添加地址"}
              </Button>
            </DialogFooter>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
