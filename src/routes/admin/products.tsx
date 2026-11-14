import { createFileRoute } from "@tanstack/react-router";
import { ProductsPage } from "@/features/admin/products-page";

export const Route = createFileRoute("/admin/products")({
  component: ProductsPage,
});
