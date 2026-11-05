@extends('layouts.admin')

@section('title', '财务中心 - 充值流水')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">充值流水</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.finance.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">财务中心</a>
                <a href="{{ route('admin.finance.recharges.export', request()->only(['start_date', 'end_date'])) }}" class="text-sm text-emerald-600 hover:text-emerald-800">导出 CSV</a>
                <span class="text-sm text-slate-500">共 {{ $transactions->total() }} 条</span>
            </div>
        </div>

        {{-- 日期筛选 --}}
        <form method="GET" action="{{ route('admin.finance.recharges') }}" class="flex items-center gap-3 bg-white rounded-lg border border-slate-200 p-4">
            <label class="text-sm text-slate-500">开始日期</label>
            <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <label class="text-sm text-slate-500">结束日期</label>
            <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-md border border-slate-300 px-3 py-1.5 text-sm">
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">筛选</button>
            <a href="{{ route('admin.finance.recharges') }}" class="text-sm text-slate-500 hover:text-slate-700">重置</a>
        </form>

        <x-table :headers="['ID', '交易号', '用户', '邮箱', '金额（元）', '类型', '时间']" :rows="$rows" empty="暂无充值记录" />
        <x-pagination :paginator="$transactions" />
    </div>
@endsection
