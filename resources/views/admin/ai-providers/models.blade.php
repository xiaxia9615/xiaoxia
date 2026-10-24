@extends('layouts.admin')

@section('title', "供应商 #{$providerId} 模型")

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">供应商 #{{ $providerId }} 模型</h3>
            @if(Route::has('admin.ai-providers.index'))
                <a href="{{ route('admin.ai-providers.index') }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800">返回供应商列表</a>
            @endif
        </div>

        <x-table
            :headers="['ID', '名称', '显示名', '输入价/1k', '输出价/1k', '状态']"
            :rows="$rows"
            empty="暂无模型" />
        {{ $models->links() }}
    </div>
@endsection
