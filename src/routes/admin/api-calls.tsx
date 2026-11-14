import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function ApiCallsPage() {
  return (
    <AdminPageLayout title="调用流水">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/api-calls")({
  component: ApiCallsPage,
});
