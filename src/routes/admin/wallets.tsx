import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function WalletsPage() {
  return (
    <AdminPageLayout title="钱包">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/wallets")({
  component: WalletsPage,
});
