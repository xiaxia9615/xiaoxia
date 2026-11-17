import { AUTH_FEATURES } from "@/lib/api";
export function ResetPasswordDialog({ open, onOpenChange }: { open: boolean; onOpenChange: (open: boolean) => void }) {
  void open; void onOpenChange;
  if (!AUTH_FEATURES.resetPassword) return null;
  return null;
}
