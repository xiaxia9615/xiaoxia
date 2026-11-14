import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function SystemPage() {
  return (
    <AdminPageLayout title="系统">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/system")({
  component: SystemPage,
});
