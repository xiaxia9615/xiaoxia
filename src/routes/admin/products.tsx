import { createFileRoute } from "@tanstack/react-router";
import { AdminPageLayout } from "@/config/components/admin/AdminPageLayout";

function ProductsPage() {
  return (
    <AdminPageLayout title="商品管理">开发中...</AdminPageLayout>
  );
}

export const Route = createFileRoute("/admin/products")({
  component: ProductsPage,
});
