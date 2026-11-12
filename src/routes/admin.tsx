import { createFileRoute } from "@tanstack/react-router";
import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { getAdminOrders, getAdminUsers } from "@/lib/api/generated";
import { formatPrice } from "@/data/products";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";

export const Route = createFileRoute("/admin")({
  component: AdminPage,
});

function AdminPage() {
  const [tab, setTab] = useState<"orders" | "users">("orders");

  return (
    <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
      <h1 className="font-display text-3xl font-bold text-ink">管理后台</h1>
      <p className="mt-1 text-sm text-muted-foreground">订单与用户管理</p>

      <div className="mt-6 flex gap-2">
        <Button
          variant={tab === "orders" ? "default" : "outline"}
          onClick={() => setTab("orders")}
        >
          订单管理
        </Button>
        <Button
          variant={tab === "users" ? "default" : "outline"}
          onClick={() => setTab("users")}
        >
          用户管理
        </Button>
      </div>

      <div className="mt-6">
        {tab === "orders" ? <OrdersTable /> : <UsersTable />}
      </div>
    </div>
  );
}

function OrdersTable() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-orders"],
    queryFn: () => getAdminOrders(),
  });

  if (isLoading) {
    return <Skeleton className="h-64 w-full rounded-xl" />;
  }

  if (isError || !data) {
    return (
      <Card>
        <CardContent className="py-12 text-center text-muted-foreground">
          订单数据加载失败
        </CardContent>
      </Card>
    );
  }

  const orders = data.list ?? [];

  return (
    <Card>
      <CardHeader>
        <CardTitle>订单列表（共 {data.total} 条）</CardTitle>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>订单号</TableHead>
              <TableHead>商品名称</TableHead>
              <TableHead>买家</TableHead>
              <TableHead className="text-right">金额</TableHead>
              <TableHead>状态</TableHead>
              <TableHead>下单时间</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {orders.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                  暂无订单
                </TableCell>
              </TableRow>
            ) : (
              orders.map((order) => (
                <TableRow key={order.id}>
                  <TableCell className="font-mono text-xs">{order.order_no}</TableCell>
                  <TableCell>{order.product_name ?? "-"}</TableCell>
                  <TableCell>{order.user_name ?? "-"}</TableCell>
                  <TableCell className="text-right font-medium">{formatPrice(order.amount)}</TableCell>
                  <TableCell>
                    <Badge variant={order.status === "paid" ? "default" : "secondary"}>
                      {order.status}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-sm text-muted-foreground">{order.created_at ?? "-"}</TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
}

function UsersTable() {
  const { data, isLoading, isError } = useQuery({
    queryKey: ["admin-users"],
    queryFn: () => getAdminUsers(),
  });

  if (isLoading) {
    return <Skeleton className="h-64 w-full rounded-xl" />;
  }

  if (isError || !data) {
    return (
      <Card>
        <CardContent className="py-12 text-center text-muted-foreground">
          用户数据加载失败
        </CardContent>
      </Card>
    );
  }

  const users = data.list ?? [];

  return (
    <Card>
      <CardHeader>
        <CardTitle>用户列表（共 {data.total} 条）</CardTitle>
      </CardHeader>
      <CardContent>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>ID</TableHead>
              <TableHead>昵称</TableHead>
              <TableHead>邮箱</TableHead>
              <TableHead>角色</TableHead>
              <TableHead>状态</TableHead>
              <TableHead>注册时间</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {users.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                  暂无用户
                </TableCell>
              </TableRow>
            ) : (
              users.map((user) => (
                <TableRow key={user.id}>
                  <TableCell className="font-mono text-xs">{user.id}</TableCell>
                  <TableCell className="font-medium">{user.name}</TableCell>
                  <TableCell className="text-sm">{user.email}</TableCell>
                  <TableCell>
                    <Badge variant={user.is_admin ? "default" : "secondary"}>
                      {user.is_admin ? "管理员" : "用户"}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Badge variant={user.is_active ? "default" : "destructive"}>
                      {user.is_active ? "正常" : "禁用"}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-sm text-muted-foreground">{user.created_at ?? "-"}</TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
}
