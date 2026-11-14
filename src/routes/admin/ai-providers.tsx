import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function AiProvidersPage() {
  return (
    <AdminPageLayout title="AI 供应商">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/ai-providers")({
  component: AiProvidersPage,
});
