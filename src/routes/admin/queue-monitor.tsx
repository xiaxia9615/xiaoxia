import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function QueueMonitorPage() {
  return (
    <AdminPageLayout title="队列监控">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/queue-monitor")({
  component: QueueMonitorPage,
});
