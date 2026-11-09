import { HandHeart } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import type { ParticipationStrategy } from "../strategies/types";
import type { ContributeFormState } from "../use-contribute-form";

/**
 * 参与弹窗：表单容器。
 * 字段、标题、校验提示全部来自参与类型策略；组件只消费 hook 的状态与动作，
 * 不接触任何后端 DTO，也不包含具体类型的业务规则。
 */
export function JoinDialog({
  open,
  onOpenChange,
  form,
  strategy,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  form: ContributeFormState;
  strategy: ParticipationStrategy;
}) {
  const { values, errors, isSubmitting, setField, submit } = form;

  return (
    <Dialog
      open={open}
      onOpenChange={(next) => {
        onOpenChange(next);
        if (!next && !isSubmitting) form.reset();
      }}
    >
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{strategy.label}</DialogTitle>
          <DialogDescription>{strategy.description}</DialogDescription>
        </DialogHeader>
        <div className="grid gap-4 py-2">
          {strategy.fields.map((field) => (
            <div key={field.name} className="grid gap-2">
              <label className="text-sm font-medium text-foreground" htmlFor={`contribute-${field.name}`}>
                {field.label}
              </label>
              <Input
                id={`contribute-${field.name}`}
                value={values[field.name] ?? ""}
                onChange={(e) => setField(field.name, e.target.value)}
                placeholder={field.placeholder}
                maxLength={field.maxLength}
                aria-invalid={Boolean(errors[field.name])}
                onKeyDown={(e) => {
                  if (e.key === "Enter") void submit();
                }}
              />
              {errors[field.name] && <p className="text-xs text-coral">{errors[field.name]}</p>}
            </div>
          ))}
        </div>
        <DialogFooter>
          <Button variant="ghost" onClick={() => onOpenChange(false)}>
            再想想
          </Button>
          <Button
            className="rounded font-mono"
            onClick={() => void submit()}
            disabled={isSubmitting}
          >
            <HandHeart className="size-4" />
            {isSubmitting ? "提交中…" : "确认加入"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
