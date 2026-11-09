import { useCallback, useMemo, useRef, useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import {
  mapFieldError,
  submitContribution,
  type ContributeItem,
} from "@/lib/api";
import {
  emptyValuesFor,
  getParticipationStrategy,
  validateValues,
  type ContributeFormValues,
} from "./schema";
import { writeJoinedFlag } from "./storage";
import { CONTRIBUTE_STREAM_KEY, type ContributeStreamData } from "./use-contribute-stream";

export interface ContributeFormState {
  values: ContributeFormValues;
  errors: Record<string, string>;
  /** 是否有未提交的修改（脏值判断） */
  dirty: boolean;
  isSubmitting: boolean;
  setField: (field: string, value: string) => void;
  reset: () => void;
  submit: () => Promise<ContributeItem | null>;
}

/**
 * 参与表单：字段状态、校验、字段级错误回填、提交与防重复。
 * 字段与校验规则来自参与类型策略，本 hook 不持有具体类型的业务规则。
 *
 * 错误分级：
 * - 字段校验失败 -> 字段级错误提示
 * - 业务错误 -> 经 mapFieldError 回填字段
 * - 系统错误 -> 统一 toast，保留用户已填内容
 * - 写入失败 -> 数据层显式上抛，此处只做呈现，不吞错
 */
export function useContributeForm(
  options: {
    typeId?: string;
    onSuccess?: (created: ContributeItem) => void;
  } = {},
): ContributeFormState {
  const strategy = useMemo(
    () => getParticipationStrategy(options.typeId),
    [options.typeId],
  );
  const initialValues = useMemo(() => emptyValuesFor(strategy), [strategy]);

  const queryClient = useQueryClient();
  const [values, setValues] = useState<ContributeFormValues>(initialValues);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);
  // 同步在途锁：React state 在同一 tick 内不同步，连点必须用 ref 拦截
  const inFlightRef = useRef(false);
  const onSuccessRef = useRef(options.onSuccess);
  onSuccessRef.current = options.onSuccess;

  const mutation = useMutation({
    mutationFn: (formValues: ContributeFormValues) =>
      submitContribution(strategy.toPayload(formValues)),
    onSuccess: (created, formValues) => {
      queryClient.setQueryData<ContributeStreamData>(CONTRIBUTE_STREAM_KEY, (prev) => {
        const rows = prev ? [created, ...prev.rows] : [created];
        return { rows, total: (prev?.total ?? 0) + 1 };
      });
      writeJoinedFlag(true);
      setValues(emptyValuesFor(strategy));
      setErrors({});
      toast.success(`已加入！感谢 ${created.name || formValues.name || "你"} 的参与`);
      onSuccessRef.current?.(created);
    },
    onError: (err) => {
      const fieldError = mapFieldError(err);
      if (fieldError) {
        setErrors({ [fieldError.field]: fieldError.message });
        return;
      }
      toast.error("提交失败，请稍后重试", { description: "你填写的内容已保留。" });
    },
  });

  const setField = useCallback((field: string, value: string) => {
    setValues((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => {
      if (!prev[field]) return prev;
      const next = { ...prev };
      delete next[field];
      return next;
    });
  }, []);

  const reset = useCallback(() => {
    setValues(emptyValuesFor(strategy));
    setErrors({});
  }, [strategy]);

  const submit = useCallback(async (): Promise<ContributeItem | null> => {
    // 防重复：在途提交期间忽略后续触发（连点/回车重复触发）
    if (inFlightRef.current || mutation.isPending || submitting) return null;

    const invalid = validateValues(strategy, values);
    if (invalid) {
      setErrors({ [invalid.field]: invalid.message });
      return null;
    }

    inFlightRef.current = true;
    setSubmitting(true);
    try {
      return await mutation.mutateAsync(values);
    } catch {
      // 错误已在 onError 分级呈现；此处不再二次提示
      return null;
    } finally {
      inFlightRef.current = false;
      setSubmitting(false);
    }
  }, [mutation, strategy, submitting, values]);

  const dirty = strategy.fields.some((f) => (values[f.name] ?? "") !== (initialValues[f.name] ?? ""));

  return {
    values,
    errors,
    dirty,
    isSubmitting: mutation.isPending || submitting,
    setField,
    reset,
    submit,
  };
}
