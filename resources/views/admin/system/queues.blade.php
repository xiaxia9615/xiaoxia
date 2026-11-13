@extends('layouts.admin')
@section('title', '队列监控')
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">队列监控</h3>
            <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回仪表盘</a>
        </div>

        @if (session('status'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-md text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">待处理任务</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $pending }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">失败任务（近50条）</p>
                <p class="text-2xl font-semibold text-red-600">{{ $failedCount }}</p>
            </div>
        </div>

        <div>
            <h4 class="text-sm font-medium text-slate-900 mb-3">失败任务列表</h4>
            @if (empty($failedRows))
                <p class="text-sm text-slate-500">暂无失败任务</p>
            @else
                <x-table
                    :headers="['ID', 'UUID', '队列', '失败时间', '操作']"
                    :rows="$failedRows"
                    empty="暂无失败任务" />
            @endif
        </div>
    </div>
@endsection
