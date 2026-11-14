import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function ApiKeysPage() {
  return (
    <AdminPageLayout title="API Keys">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/api-keys")({
  component: ApiKeysPage,
});
