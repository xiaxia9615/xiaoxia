@extends('layouts.admin')
@section('title', '技能管理')
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">技能管理</h3>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.skills.calls') }}" class="text-sm text-indigo-600 hover:text-indigo-800">调用记录</a>
                @if(Route::has('admin.dashboard'))
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回仪表盘</a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">技能数</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['skills'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">总调用</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['calls_total'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">今日调用</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['calls_today'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-slate-500">成功调用</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $stats['calls_success'] }}</p>
            </div>
        </div>

        <x-table
            :headers="['ID', '产品', 'Endpoint', '方法', '定价类型', 'API Key', '调用数', '操作']"
            :rows="$rows"
            empty="暂无技能" />

        {{ $skills->links() }}
    </div>
@endsection
