import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function LedgerPage() {
  return (
    <AdminPageLayout title="Ledger">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/ledger")({
  component: LedgerPage,
});
