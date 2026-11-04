@extends('layouts.admin')

@section('title', '商品管理')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">商品管理</h2>
            <span class="text-sm text-slate-500">共 {{ $products->total() }} 个商品</span>
        </div>

        @if (session('status'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-md text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.products.index') }}"
               class="px-3 py-1.5 text-sm rounded-md {{ request()->query('status') === null ? 'bg-indigo-50 text-indigo-700' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">全部</a>
            <a href="{{ route('admin.products.index', ['status' => 1]) }}"
               class="px-3 py-1.5 text-sm rounded-md {{ request()->query('status') === '1' ? 'bg-indigo-50 text-indigo-700' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">已上架</a>
            <a href="{{ route('admin.products.index', ['status' => 2]) }}"
               class="px-3 py-1.5 text-sm rounded-md {{ request()->query('status') === '2' ? 'bg-indigo-50 text-indigo-700' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">已下架</a>
        </div>

        @php
            $statusLabels = [
                1 => ['已上架', 'bg-emerald-50 text-emerald-700'],
                2 => ['已下架', 'bg-slate-100 text-slate-500'],
            ];

            $rows = $products->map(function ($product) use ($statusLabels) {
                [$label, $class] = $statusLabels[$product->status] ?? [(string) $product->status, 'bg-slate-100 text-slate-500'];
                $statusBadge = '<span class="px-2 py-0.5 text-xs rounded-full ' . $class . '">' . $label . '</span>';

                $action = (int) $product->status === 1
                    ? '<form method="POST" action="' . route('admin.products.forceArchive', $product->id) . '" onsubmit="return confirm(\'确认强制下架该商品？\');">'
                        . '@csrf'
                        . '<button type="submit" class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-md hover:bg-red-100">强制下架</button>'
                        . '</form>'
                    : '—';

                return [
                    (string) $product->id,
                    e($product->name),
                    e($product->provider?->name ?? '-'),
                    e(number_format($product->price / 1000, 2)) . ' 元',
                    $statusBadge,
                    e(optional($product->created_at)?->format('Y-m-d H:i:s') ?? '-'),
                    $action,
                ];
            })->all();
        @endphp

        <x-table :headers="['商品ID', '名称', '卖家', '价格', '状态', '创建时间', '操作']" :rows="$rows" empty="暂无商品" />
        <x-pagination :paginator="$products" />
    </div>
@endsection
