import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function SkillReviewsPage() {
  return (
    <AdminPageLayout title="技能审核">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/skill-reviews")({
  component: SkillReviewsPage,
});
