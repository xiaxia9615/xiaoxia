@extends('layouts.admin')

@section('title', 'AI 供应商')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">AI 供应商</h3>
            @if(Route::has('admin.dashboard'))
                <a href="{{ route('admin.dashboard') }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800">返回仪表盘</a>
            @endif
        </div>

        <x-table
            :headers="['ID', '名称', '显示名', '驱动', '模型', 'API Key', '模型数', '状态', '操作']"
            :rows="$rows"
            empty="暂无供应商" />
        {{ $providers->links() }}
    </div>
@endsection
