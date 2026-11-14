import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function DashboardPage() {
  return (
    <AdminPageLayout title="仪表盘">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/")({
  component: DashboardPage,
});
