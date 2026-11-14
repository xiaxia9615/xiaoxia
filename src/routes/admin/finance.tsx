import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function FinancePage() {
  return (
    <AdminPageLayout title="财务中心">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/finance")({
  component: FinancePage,
});
