@extends('layouts.admin')

@section('title', '订单管理')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">订单管理</h2>
            <span class="text-sm text-slate-500">共 {{ $orders->total() }} 个订单</span>
        </div>

        @if (session('status'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-md text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @php
            $statusLabels = [
                'pending' => ['待支付', 'bg-amber-50 text-amber-700'],
                'paid' => ['已支付', 'bg-emerald-50 text-emerald-700'],
                'cancelled' => ['已取消', 'bg-slate-100 text-slate-500'],
                'completed' => ['已完成', 'bg-indigo-50 text-indigo-700'],
                'refunded' => ['已退款', 'bg-red-50 text-red-700'],
            ];

            $rows = $orders->map(function ($order) use ($statusLabels) {
                $status = $order->status->value;
                [$label, $class] = $statusLabels[$status] ?? [$status, 'bg-slate-100 text-slate-500'];
                $statusBadge = '<span class="px-2 py-0.5 text-xs rounded-full ' . $class . '">' . $label . '</span>';

                $action = $order->status->value === 'paid'
                    ? '<form method="POST" action="' . route('admin.orders.refund', $order->id) . '" onsubmit="return confirm(\'确认退款该订单？\');">'
                        . '@csrf'
                        . '<button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-md hover:bg-red-100">退款</button>'
                        . '</form>'
                    : '—';

                return [
                    e($order->order_no),
                    e($order->user?->name ?? '-'),
                    e($order->product_name),
                    e(number_format($order->amount / 1000, 2)) . ' 元',
                    $statusBadge,
                    e(optional($order->created_at)?->format('Y-m-d H:i:s') ?? '-'),
                    $action,
                ];
            })->all();
        @endphp

        <x-table :headers="['订单号', '买家', '商品名', '金额', '状态', '下单时间', '操作']" :rows="$rows" empty="暂无订单" />
        <x-pagination :paginator="$orders" />
    </div>
@endsection
