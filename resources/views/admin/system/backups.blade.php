@extends('layouts.admin')
@section('title', '数据库备份')
@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">数据库备份</h3>
            @if(Route::has('admin.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">返回仪表盘</a>
            @endif
        </div>
        <x-table
            :headers="['文件名', '大小', '创建时间']"
            :rows="$rows"
            empty="暂无备份" />
    </div>
@endsection
