@extends('layouts.admin')
@section('title', '调用记录')
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">调用记录</h3>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.skills.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回技能列表</a>
                @if(Route::has('admin.dashboard'))
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回仪表盘</a>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('admin.skills.calls') }}" class="flex items-center gap-3">
            <input type="text" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="状态码（如 200）" class="border-slate-300 rounded-md text-sm" />
            <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">筛选</button>
            <a href="{{ route('admin.skills.calls') }}" class="px-3 py-1.5 text-sm text-slate-500 hover:text-slate-700">清除</a>
        </form>

        <x-table
            :headers="['时间', 'Request ID', '用户ID', '产品ID', '状态', '耗时', '费用', '操作']"
            :rows="$rows"
            empty="暂无调用记录" />

        {{ $calls->links() }}
    </div>
@endsection
