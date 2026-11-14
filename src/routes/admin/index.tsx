import { createFileRoute } from "@tanstack/react-router";
import { lazy } from "react";

const DashboardPage = lazy(() =>
  import("@/features/admin/dashboard-page").then((m) => ({
    default: m.DashboardPage,
  })),
);

export const Route = createFileRoute("/admin/")({
  component: DashboardPage,
});
