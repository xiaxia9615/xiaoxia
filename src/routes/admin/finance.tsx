import { createFileRoute } from "@tanstack/react-router";
import { FinancePage } from "@/features/admin/finance-page";

export const Route = createFileRoute("/admin/finance")({
  component: FinancePage,
});
