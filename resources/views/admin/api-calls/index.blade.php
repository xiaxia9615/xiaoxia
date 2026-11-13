@extends('layouts.admin')
@section('title', '调用流水')
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">调用流水</h3>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回仪表盘</a>
        </div>

        <form method="GET" class="bg-white rounded-lg shadow-sm p-4 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs text-slate-500 mb-1">用户 ID</label>
                <input type="number" name="user_id" value="{{ $filters['user_id'] }}" class="w-full px-2 py-1.5 border border-slate-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">技能 ID</label>
                <input type="number" name="product_id" value="{{ $filters['product_id'] }}" class="w-full px-2 py-1.5 border border-slate-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">开始日期</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="w-full px-2 py-1.5 border border-slate-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">结束日期</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="w-full px-2 py-1.5 border border-slate-300 rounded-md text-sm">
            </div>
            <div class="sm:col-span-4 flex gap-2">
                <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">筛选</button>
                <a href="{{ route('admin.api-calls.index') }}" class="px-3 py-1.5 bg-slate-100 text-slate-600 text-sm rounded-md hover:bg-slate-200">重置</a>
            </div>
        </form>

        <x-table
            :headers="['时间', 'Request ID', '用户', '技能', '状态码', '耗时', '费用(元)', '操作']"
            :rows="$rows"
            empty="暂无调用记录" />

        {{ $calls->links() }}
    </div>
@endsection
