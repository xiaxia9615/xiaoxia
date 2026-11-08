@extends('layouts.admin')

@section('title', '审计日志')

@section('content')
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">审计日志</h2>
            <span class="text-sm text-slate-500">共 {{ $logs->total() }} 条记录</span>
        </div>

        <form method="GET" class="flex flex-wrap gap-3">
            <input type="text" name="action" value="{{ request('action') }}" placeholder="操作类型" class="px-3 py-1.5 text-sm border border-slate-300 rounded-md" />
            <input type="text" name="target_type" value="{{ request('target_type') }}" placeholder="目标类型" class="px-3 py-1.5 text-sm border border-slate-300 rounded-md" />
            <input type="text" name="operator_id" value="{{ request('operator_id') }}" placeholder="操作者ID" class="px-3 py-1.5 text-sm border border-slate-300 rounded-md w-28" />
            <button type="submit" class="px-4 py-1.5 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">筛选</button>
            <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-1.5 text-sm text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50">重置</a>
        </form>

        <x-table :headers="['ID', '操作', '目标类型', '目标ID', '操作者ID', 'IP', '时间']" :rows="$rows" />

        <x-pagination :paginator="$logs" />
    </div>
@endsection
