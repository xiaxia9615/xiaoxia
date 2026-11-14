import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function SkillsPage() {
  return (
    <AdminPageLayout title="技能">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/skills")({
  component: SkillsPage,
});
